  </div>
</div>

	
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