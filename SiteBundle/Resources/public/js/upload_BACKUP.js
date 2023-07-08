var fileInput = document.querySelector('#file'),
    progress = document.querySelector('#progress-upload-bar');

if (fileInput)
    fileInput.onchange = function() {

	var xhr = new XMLHttpRequest();

	xhr.open('POST', 'http://exemple.com');

	xhr.upload.onprogress = function(e) {
            progress.value = e.loaded;
            progress.max = e.total;
	};
    
	xhr.onload = function() {
            alert('Upload terminé !');
	};
	
	var form = new FormData();
	form.append('file', fileInput.files[0]);

	xhr.send(form);
    };