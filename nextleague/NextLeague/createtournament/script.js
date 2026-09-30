// Este script muestra una vista previa del archivo subido
// y agrega un botón para poder eliminarlo si el usuario se equivocó.

(function () {

  // Crea el botón de eliminar y lo agrega dentro del contenedor
  function crearBotonEliminar(input, container) {
    var boton = document.createElement('button');
    boton.type = 'button'; // para que no envíe el formulario
    boton.className = 'btn-eliminar';
    boton.textContent = 'Eliminar archivo';

    boton.addEventListener('click', function () {
      input.value = '';       // borra el archivo del input
      container.innerHTML = ''; // borra la vista previa
    });

    container.appendChild(boton);
  }

  // Muestra la vista previa según el tipo de archivo
  function mostrarPreview(input, container) {
    container.innerHTML = ''; // limpia lo que había antes

    var archivo = input.files && input.files[0];
    if (!archivo) return;

    // Si es una imagen, mostramos el preview con una miniatura
    if (archivo.type.indexOf('image') !== -1) {
      var img = document.createElement('img');
      img.className = 'preview-img';

      var url = URL.createObjectURL(archivo);
      img.src = url;
      img.onload = function () {
        URL.revokeObjectURL(url); // liberamos memoria
      };

      container.appendChild(img);

    } else if (archivo.type === 'application/pdf') {
      // Si es un PDF, no se puede mostrar como imagen,
      // así que solo mostramos el nombre del archivo
      var texto = document.createElement('p');
      texto.textContent = '📄 ' + archivo.name;
      container.appendChild(texto);

    } else {
      // Cualquier otro tipo de archivo (no debería pasar por el accept, pero por las dudas)
      var otro = document.createElement('p');
      otro.textContent = 'Archivo no válido: ' + archivo.name;
      container.appendChild(otro);
    }

    // Siempre agregamos el botón de eliminar al final
    crearBotonEliminar(input, container);
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Lista de inputs de archivo que tienen vista previa
    var ids = ['subir-imagen', 'subir-reglas'];

    ids.forEach(function (id) {
      var input = document.getElementById(id);
      var container = document.getElementById('preview-' + id);
      if (!input || !container) return;

      // Cada vez que el usuario elige un archivo, mostramos la preview
      input.addEventListener('change', function () {
        mostrarPreview(input, container);
      });
    });
  });

})();
