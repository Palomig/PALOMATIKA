{{-- KaTeX math rendering — include after head-config in topic/ege layouts --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
        onload="renderMathWithDisplayStyle()"></script>
<script>
    function renderMathWithDisplayStyle() {
        document.querySelectorAll('body *').forEach(el => {
            if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) {
                let text = el.childNodes[0].textContent;
                if (text.includes('$') && text.includes('\\frac')) {
                    text = text.replace(/\\frac\{/g, '\\displaystyle\\frac{');
                    el.childNodes[0].textContent = text;
                }
            }
        });
        renderMathInElement(document.body, {
            delimiters: [
                {left: '$$', right: '$$', display: true},
                {left: '$', right: '$', display: false},
                {left: '\\(', right: '\\)', display: false},
                {left: '\\[', right: '\\]', display: true}
            ]
        });
    }
</script>
