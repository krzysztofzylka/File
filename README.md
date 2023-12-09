# Install
```bash
composer require krzysztofzylka/file
```

# Methods
## Repair path
```php
\Krzysztofzylka\File\File::repairPath('path')
```
## Create directory
Permission 0755 is default
```php
\Krzysztofzylka\File\File::mkdir('path', 0755)
```
or
```php
\Krzysztofzylka\File\File::mkdir(['path', 'path2'])
```
## Remove
```php
\Krzysztofzylka\File\File::unlink('path')
```
## Recursive scan directory
```php
\Krzysztofzylka\File\File::scanDir('directory path')
```
## Create file
```php
\Krzysztofzylka\File\File::touch('path', 'value') //value is not required
```
## Copy file
```php
\Krzysztofzylka\File\File::copy('source path', 'destination path')
```
## Copy directory
```php
\Krzysztofzylka\File\File::copyDirectory('source path', 'destination path');
```
## Get file extension
```php
\Krzysztofzylka\File\File::getExtension('file path')
```
## Get file content type
```php
\Krzysztofzylka\File\File::getContentType('file extension')
```