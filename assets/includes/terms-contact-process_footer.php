<!--include "contact_footer.php"-->
	<link href="assets/css/contact-style.css" rel="stylesheet" type="text/css">
        <script src="/assets/js/Terms-ContactForm.js"></script>
        <script>
        new ContactForm({
            container: document.querySelector('.contact-form'),
            // The PHP file path that processes the form data
            php_file_url: 'terms-contact-process.php'
        });
        </script>
<?php /*
	<link href="assets/css/contact-style.css" rel="stylesheet" type="text/css">
        <script src="/assets/js/Terms-ContactForm.js"></script>
        <script>
        new ContactForm({
            container: document.querySelector('.contact-form'),
            // The PHP file path that processes the form data
            php_file_url: 'terms-contact-process.php'
        });
        </script>
?*/?>