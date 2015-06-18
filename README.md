# PHP-to-PP2-Assembly
A compiler of PHP to the TU/e PP2 Assembly code I wrote for university.

##Build Instructions
Make sure you have java and php5.6 or higher installed.

You need to download the Assembler9.jar of the TU/e university in the src folder. Then, to allow building you will have to edit php.ini to enable the creation of the phar. The relevant setting is phar.readonly, it should be set to "Off". It would be a good idea to reset this setting to On after building for security reasons. See http://php.net/manual/en/phar.configuration.php for more info about this setting.

Now simply run the create-phar.php
```bash
chmod +x create-phar.php
./create-phar.php
```

You should now have a compiler.phar and the assembler in the build directory.

##Usage
To get the usage, run `compiler.phar --help`.

As an example, to compile the example files (the code my team wrote for one of a university project) you would use the following:
```bash
php ./build/compiler.phar --comments --assemble --file='./example/example.php'
```

I do not recommend actually using this compiler, it works but has a lot of weird quirks because it does not actually understand your code.
If you do want to use it, you can include functions.php which can be found in ./src and in ./example to make your php IDE understand the custom functions.

##licence
MIT License