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

Esto es software libre. Está liberador bajo los términos de la siguiente licencia BSD

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
		
		(No he logrado instalar la librería en versiones estables de Yii 2 sin añadir la referencia adicionarl de yurkinx por la forma extraña de obtener versiones de referencias de composer. Si alguien sabe cómo lograr instalarla sin añadir esta referencia superflua al fichero principal de composer.json, que por favor lo diga :sweat_smile:).
	
* Modificar el fichero de configuración _'config/web.php'_


			'modules' => [
				'fileupload' => [
					'class' => 'cyneek\yii2\fileupload\Module'
				]
				// set custom modules here
			],


* Aplicar la migración del directorio de migraciones
	* ```php yii migrate --migrationPath=@vendor/cyneek/yii2-fileupload/migrations```

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

	* El método "public static function default_values()". Debe tener en su interior un return a un array con 
