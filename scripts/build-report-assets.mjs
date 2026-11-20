import fs from 'node:fs/promises';
import postcss from 'postcss';

// Bootstrap styles apply only to operational report content. Filament owns
// the surrounding navigation, account controls, tables and dialogs.
const sources = [
    'node_modules/bootstrap/dist/css/bootstrap.css',
    'node_modules/datatables.net-dt/css/dataTables.dataTables.css',
    'public/css/simaster.css',
];
const scoped = [];
for (const filename of sources) {
    const root = postcss.parse(await fs.readFile(filename, 'utf8'), { from: filename });
    root.walkAtRules('charset', rule => rule.remove());
    root.walkComments(comment => { if (comment.text.includes('sourceMappingURL=')) comment.remove(); });
    root.walkRules(rule => {
        let ancestor = rule.parent;
        while (ancestor) {
            if (ancestor.type === 'atrule' && ancestor.name.endsWith('keyframes')) return;
            ancestor = ancestor.parent;
        }
        rule.selectors = rule.selectors.map(selector => {
            if (selector === ':root' || selector === 'body' || selector === 'html') return '.simaster-report';
            if (selector.startsWith('[data-bs-theme=')) {
                return selector.replace(/^(\[data-bs-theme=[^\]]+\])/, '.simaster-report$1');
            }
            if (selector.startsWith('body ')) return `.simaster-report ${selector.slice(5)}`;
            return `.simaster-report ${selector}`;
        });
    });
    scoped.push(root.toString());
}
await fs.writeFile('resources/css/report-scoped.css', scoped.join('\n'));
await fs.mkdir('public/vendor/simaster', { recursive: true });
for (const [source, target] of [
    ['node_modules/jquery/dist/jquery.min.js', 'jquery-3.7.1.min.js'],
    ['node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', 'bootstrap-5.3.3.bundle.min.js'],
    ['node_modules/datatables.net/js/dataTables.min.js', 'datatables-2.1.8.min.js'],
]) {
    const content = (await fs.readFile(source, 'utf8')).replace(/\/\/# sourceMappingURL=.*$/gm, '');
    await fs.writeFile(`public/vendor/simaster/${target}`, content);
}
console.log('Report CSS scoped; local JavaScript assets published.');
