 <footer id="footer" class="footer mt-auto py-3">
  <div class="container"> 
    <div class="copyright">
         <p id="copyright">
      <span class="text-muted"> 
      


 <script>
    window.addEventListener('load', onLoad);

    function onLoad () {
      const copyrightEl = document.getElementById('copyright');

      if (copyrightEl) {
        const currentYear = new Date().getFullYear();
        const copyrightText = `Copyright &copy; 2022 - ${currentYear} &nbsp; GlitchWizard Solutions, LLC. &nbsp; All Rights Reserved`;

        copyrightEl.innerHTML = copyrightText;
      }
    }
     </script>
     </span></p>
    </div>
</div>
  </footer>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
<?php include includes_path . 'site-close.php'; ?>