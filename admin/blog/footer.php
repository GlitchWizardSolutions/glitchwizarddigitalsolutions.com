  </div>
</div>

    <!--DataTables-->
    <script src="https://cdn.datatables.net/v/bs5/dt-2.1.8/r-3.0.3/datatables.min.js"></script>
	
	<script>
		function countText() {
			let text = document.post_form.title.value;
			
			document.getElementById('characters').innerText = text.length;
			//document.getElementById('words').innerText = text.length == 0 ? 0 : text.split(/\s+/).length;
			//document.getElementById('rows').innerText = text.length == 0 ? 0 : text.split(/\n/).length;
		}
	</script>
  
  </body>
</html>