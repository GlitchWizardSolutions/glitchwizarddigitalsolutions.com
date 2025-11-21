    document.querySelectorAll('.content .toolbar .format-btn').forEach(element => element.onclick = () => {
        let textarea = document.querySelector('.content textarea');
        let text = '<strong></strong>';
        text = element.classList.contains('fa-italic') ? '<i></i>' : text;
        text = element.classList.contains('fa-underline') ? '<u></u>' : text;
        textarea.setRangeText(text, textarea.selectionStart, textarea.selectionEnd, 'select');
    });