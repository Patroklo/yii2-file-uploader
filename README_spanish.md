# Sistema de gestión y subida de ficheros para Yii2

Sistema de gestión y subida de ficheros para Yii 2 que enlaza objetos de tipo ActiveRecord con ficheros o galerías.

## ¿Qué es el sistema de gestión y subida de ficheros?

Este módulo añade una nueva extensión a ActiveRecord que permite a los desarrolladores enlazar ficheros o sus copias a otros objetos de tipo ActiveRecord.

Desarrollado por Joseba Juániz ([@Patroklo](http://twitter.com/Patroklo))

[Versión en inglés del Readme](https://github.com/Patroklo/yii2-file-uploader/blob/master/Readme.md)

## Requisitos mínimos

* Yii2
* Php 5.4 o superior

## Planes de futuro

* Ninguno por ahora.

## Licencia

Esto es software libre. Está liberado bajo los términos de la siguiente licencia BSD

Copyright (c) 2014, by Cyneek
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:
1. Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
3. Neither the name of Cyneek nor the names of its contributors
   may be used to endorse or promote products derived from this software
   without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDER "AS IS" AND ANY
EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

## Instalación

* Instalar [Yii 2](http://www.yiiframework.com/download)
* Instalar el paquete vía [composer](http://getcomposer.org/download/)
		
		`"cyneek/yii2-fileupload": "dev-master", "yurkinx/yii2-image": "@dev"`
		
		(No he logrado instalar la librería en versiones estables de Yii 2 sin añadir la referencia adicional de yurkinx por la forma extraña de obtener versiones de referencias de composer. Si alguien sabe cómo lograr instalarla sin añadir esta referencia superflua al fichero principal de composer.json, que por favor lo diga :sweat_smile:).
	
* Modificar el fichero de configuración _'config/web.php'_


			'modules' => [
				'fileupload' => [
					'class' => 'cyneek\yii2\fileupload\Module'
				]
				// set custom modules here
			],


* Aplicar la migración del directorio de migraciones
> php yii migrate --migrationPath=@vendor/cyneek/yii2-fileupload/migrations

* Asegurarse de que Php tiene permisos de escritura en el directorio "web/" de la instalación de Yii 2.
* Profit!

## Definición

La librería permite a los usuarios el subir o gestionar ficheros a través de objetos que extienden a la clase FileModel.php. Esta clase a su vez extiende a ActiveRecord y puede ser usada en operaciones de bases de datos y formularios web.

Estos objetos deben de estar siempre enlazados a otro objeto de tipo ActiveRecord o FileOwnerActiveRecord, el cual será capaz de obtener todos estos ficheros que tiene enlazados a través de operaciones de bases de datos.

El paquete contiene dos diferentes modelos:

* FileModel.php : es el modelo principal. Todos los objetos fichero extenderán a este archivo. Para poder usarlo apropiadamente el desarrollador tiene que rellenar una serie de configuraciones básicas que se explicarán más adelante en esta guía.

* FileOwnerActiveRecord.php : es un modelo que extiende ActiveRecord. Puede ser usado para ser extendido por objetos que tienen fichero enlazados a él. Provee azucar sintáctico para obtener uno o más ficheros que están enlazados a ellos y para enlazar estos mismos objetos al mismo.

El paquete usa la librería "flysystem", la que permite a los usuarios trabajar con ficheros en servidores locales, via ftp, dropbox, amazon aws, etc... Todas estas configuraciones están potencialmente disponibles en esta extensión para su uso a la hora de gestionar ficheros. Dado que cada modelo extendido puede tener su propia configuración de flysystem, se puede trabajar al mismo tiempo con varios ficheros de diferentes fuentes, como Amazon aws, local, ftp, etc...

El paquete de fileupload también usa una librería de imágenes que permite a los desarrolladores el manipularlas mientras se realiza su inserción o edición o de forma manual usando librerías de GD o Imagick (dependiendo de la instalación Php).

### Creando un modelo de ficheros

Para poder usar el paquete, primero hay que definir un modelo que se encargará de gestionar ese tipo de ficheros. Cada uno de los objetos del modelo enlazará a un fichero diferente que podrá ser enlazado a cualquier otro objeto de tipo ActiveRecord del sistema. Pero para poder hacer esto hay que realizar una serie de configuraciones básicas:

* Crear un nuevo fichero Php con una clase que extienda a "FileModel".

* Añadir la siguiente configuración básica:

	* El método "default_values()". Debe tener en su interior un return a un array con un array con un string cuyo índice sea "file_type" y que debe ser diferente a cualquier otro FileModel que exista en esta instalación de Yii2. También un "upload_path" que definirá el directorio donde se almacenará los archivos. No es necesario que sea único, pero es recomendado. 

	Ejemplo:
	
		return [
			'file_type'	=> 'nombre_que_define_el_modelo_en_la_base_de_datos',
			'upload_path'	=> 'dirección_donde_se_almacenarán_los_ficheros'
		];
		
	Este código creará objetos conectados a la base a través del string "nombre_que_define_el_modelo_en_la_base_de_datos" y se almacenarán en la carpeta "dirección_donde_se_almacenarán_los_ficheros". Hay que recordar que dentro de este directorio se definirán una serie de subcarpetas con el formato "YYYY"/"MM"/"DD" para mantener los ficheros almacenados de forma más organizada.
			
	* Parámetro opcional "file_required". Es un boleano que define la obligatoriedad de subir el fichero cuando se hace una llamada de formulario con este sistema. Cuando es "False" Yii2 no marcará el fichero como requerido. Está definido por defecto como "True", siendo entonces de subida opcional.

	* Optional method "_copies_data()". Defines if the system will make copies of the uploaded file when the "save()" method it's called.
	* Método opcional "_copies_data()". Define si el sistema hará copias de los ficheros subidos cuando se llame al método "save()".
	
	Por ejemplo:
	
		return [
				'operations' => [
									['action' => 'resize', 
									 'height' => '100', 
									 'width' => 100, 
									 'size' => 5000]
								],
				'file2' => [ more operations ]
				];
				
	Este código hará una copia del fichero subido (en este caso debería ser una imagen) al que se le cambiará el tamaño a 100x100 manteniendo su ratio de aspecto (uno de los lados puede que no llegue a tener exáctamente 100 píxeles) y rebajará la calidad de la imagen hasta que llegue a ocupar 5000 bytes o menos.
	Además se usarán las keys del array que contiene las operaciones para denominar a estas copias en la columna "child_name", lo que permitirá el distinguir entre los diferentes ficheros de copia.

	Por ejemplo:
	
	return 3;
	
	También se pueden definir numerales. En este caso hará 3 copias exactas del fichero subido.
	
	
### Crear una clase tipo FileOwnerActiveRecord

Es recomendable usar objetos que extiendan a FileOwnerActiveRecord cuando queramos conectarlos a ficheros. La razón es que esta clase trae un grupo de métodos que ayudan a este objeto con las conexiones y accesos a los ficheros que tienen asignados. Si se quiere usar otro tipo de clase con esta librería, entonces habrá que hacer estas conexiones de forma manual para cada clase.

Para poder utilizar un objeto que extienda a FileOwnerActiveRecord es necesario:

* Crear un nuevo fichero que contenga una clase que extienda a "FileOwnerActiveRecord".

* Añadir un método "linkedFiles()" que contendrá un array con la configuración para todos los ficheros que podrían ser enlazados a este objeto.

		protected function linkedFiles()
		{
			return ['nameOfFieldToAccessFiles' => ExampleFileModel::className(),
						'file' => AnotherFileModel::className()];
		}
		
Importante: No se pueden crear métodos dentro de esta clase llamados "getNameOfFieldToAccessFiles()" o "getFile()" porque el sistema los usa automáticamente para obtener los ficheros vía relaciones entre tablas de Yii2.

## Uso básico

Una vez el modelo de ficheros está hecho, se puede comentar a usarlo.

### Enlazar un File Model a su propietario

Cuando un fichero es enlazado a otro objeto, entonces este objeto es designado como su "propietario", dado que ahora puede acceder a éste en cualquier momento.

La forma más sencilla de hacer esto es que el otro modelo, el del propietario, extienda a la clase FileOwnerActiveRecord. De esa forma se puede usar el azúcar sintáctico incorporado a esta clase (es conveniente recordar que es tan sólo una extensión a ActiveRecord con algunos métodos adicionales). 

	// Creamos objetos vacíos
	$file = new ExampleFileModel();
	$file2 = new AnotherFileModel();
	
	// obtenemos un objeto cargado de una clase que extienda a FileOwnerActiveRecord
	$object = new ImportantObject::findOne(1);
	
	// Enlazamos el objeto a los archivos. Esto se hará cuando la base de datos lance el método save() para *$file* y *$file2*. Pero hay que recordar que no se puede salvar un objeto de fichero vacío.
	$object->linkFile($file);
	$object->linkFile($file2);
	
Otra forma de hacer un enlace, recomendado cuando el objeto propietario no extienda la clase FileOwnerActiveRecord:

	// Hacemos objetos vacíos
	$file = new ExampleFileModel();
	$file2 = new AnotherFileModel();
	
	// obtenemos un objeto cargado de una clase que **NO** extienda a FileOwnerActiveRecord
	$object = new ImportantObject::findOne(1);
	
	// Enlace inverso de dos ficheros a un objeto.
	$file->linkOwner($object);
	$file2->linkOwner($object);
	
Además, como se ha podido ver, un propietario puede tener varios objetos fichero de diferentes clases enlazados a este al mismo tiempo. 

### Acceder a un fichero enlazado a un objeto propietario (sólo para propietarios que extiendan a FileOwnerActiveRecord)

Para acceder a un fichero enlazado a un objeto propietario (si hay más de un fichero de ese mismo tipo enlazados al mismo tiempo, tan sólo se obtendría el último de ellos con este método) usaremos el nombre con el que está definido en su padre como parámetro (exáctamente como si estuviéramos trabajando con relaciones en Yii2)

	// Obtener un objeto cargado de una clase que extiende a FileOwnerActiveRecord
	$object = new ImportantObject::findOne(1);
	
	$file = $object->nameOfFieldToAccessFilesOne;
	$file2 = $object->fileOne;
	
También es posible usar:
	
	// Obtener un objeto cargado de una clase que extiende a FileOwnerActiveRecord
	$object = new ImportantObject::findOne(1);
	
	$file = $object->nameOfFieldToAccessFiles;
	$file2 = $object->file;

### Acceder a todos los ficheros enlazados a un objeto Propietario (sólo para propietarios que extiendan a la clase FileOwnerActiveRecord)

Esto retornará un array con todos los ficheros de ese tipo enlazados a este objeto propietario:

	// Obtener un objeto cargado de una clase que extiende a FileOwnerActiveRecord
	$object = new ImportantObject::findOne(1);
	
	$file = $object->nameOfFieldToAccessFilesAll;
	$file2 = $object->fileAll;

### Formulario simple con Yii 2 y file uploading

(Ver directorio example)

### Multiupload

Es posible usar la librería junto con extensiones de multiupload gracias al método estático "Filemanager::multiUpload" que retornará un array con todos los ficheros subidos a la clase FileModel que se le ha pasado como parámetro. 

(Ver directorio example para más información)

### Guardar un objeto file

Además del método "save()" heredado de la clase ActiveRecord, hay un nuevo método que puede guardar datos del objeto fichero llamado "saveAs()" con dos parámetros adicionales además del ya incorporado por defecto al método save()

* Filename:
> String que definirá cómo se llamará el archivo una vez se haya guardado en su localización definitiva. Atención: si existe un fichero en esa carpeta con el mismo nombre será sobreescrito de forma silenciosa.

* Operaciones:
> Un array que define las operaciones que se realizarán en el fichero una vez que haya sido insertado / modificado.

### Operaciones automáticas sobre ficheros

Definido en operaciones de copia o en el método "saveAs", esto son operaciones automáticas que modificarán el fichero una vez que se haya insertado / modificado.

Es posible anidar diferentes operaciones en el array para un fichero en particular. Esto permite al desarrollado el realizar operaciones más complejas al separarlas en múltiples tareas sencillas.

* action (string) (obligatorio)
    * resize
    > Modifica el tamaño de la imagen a las dimensiones seleccionadas (altura y anchura)
    * crop
    > Recorta la imagen con las dimensiones seleccionadas (altura, anchura, punto de inicio x y punto de inico y). Si no se definen puntos de inicio, se pondrán por defecto 0,0.
    * size
    > Guarda la imagen con menos calidad para reducir su peso. Irá rebajando su calidad hasta que el espacio que ocupa en disco sea menos que el definido)
    * crop_middle
    > Recorta la imagen justo en su mitad, solo es necesario en este caso definir su nueva altura y anchura.
    
* height (integer) (opcional)
> Define en píxeles la altura de la imagen después de su redimensión o recorte.

* width (integer) (opcional)
> Define en píxeles la anchura de la imagen después de su redimensión o recorte.

* master (integer) (opcional)
> Solo usado con la acción **resize**, define cómo se redimensionará la imagen:
		const NONE    = no constrain
		const WIDTH   = reduces by width
		const HEIGHT  = reduces by height
		const AUTO    = max reduction
		const INVERSE = minimum reduction
		const PRECISE = doesn't keep image ratio
		
* offset_x (integer) (opcional) 
> Define el punto de inicio en el eje de las x donde el sistema comenzará a recortar la imagen.

* offset_y (integer) (opcional) 
> Define el punto de inicio en el eje de las y donde el sistema comenzará a recortar la imagen.

* size (integer) (opcional) 
> En bytes, el tamaño máximo que podrá tener el fichero de tipo imagen. Si es más grande, entonces el sistema rebajará su calidad hasta que deje de serlo.


Serán definidas como un array

	  array['action'	=> 'resize',
	 		'height'	=> NULL/pixels,
	 		'width'		=> NULL/pixels,
	  		'master'	=> NULL/int, (constrain reduction, defined like:
	  									const NONE    = no constrain
										const WIDTH   = reduces by width
										const HEIGHT  = reduces by height
										const AUTO    = max reduction
										const INVERSE = minimum reduction
										const PRECISE = doesn't keep image ratio)
	  		'offset_x'	=> NULL/int,
	  		'offset_y'	=> NULL/int,
	  		'size'		=> NULL/bytes
	  	];
	  	

### Hacer la copia de un fichero

También es posible hacer la copia del fichero seleccionado a través del método *makeCopy* que acepta los siguientes parámetros:

* operations (array) (opcional) 
> Operaciones sobre ficheros, descritas en el punto anterior.

* child_name (string) (opcional) 
> Un string para dar nombre a esta nueva copia. Esto permitirá al desarrollador el buscar copias específicas en cada fichero.