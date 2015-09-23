Nature Quizzer
--------------

Simple web-based adaptive learning system for teaching users biological concepts such as plants, animals,...

Dependencies
============

General:

 - PHP >= 5.6
 - PostgreSQL >= 9.3
 - Node.js and its dependencies captured in `package.json`
 - Composer and its dependencies captured in `composer.json`

Installing
==========

0. Update to proper selected GIT branch/tag.

1. Install development dependencies via `node`:

```
$ cd <project path>
$ npm update

```

2. Install runtime (aka PHP) dependencies via `composer`:
 
```
$ cd <project path>
$ composer update
```

3. Run `gulp` to build css, templates and javascript files:

```
$ cd <project path>
$ gulp
```

There are two gulp targets: `gulp development` will watch for changes, whereas `gulp production` will minify the JS output. 

4. Create local configuration (database credentials, FB and Google+ API keys etc)

```
$ cd <project path>
$ vim app/config.local.neon
```

5. Create database and import ``sources/itis.sql.gz`` from ``nature-quizzer-packages`` repository.
 
```
$ psql -U postgres -d nature-quizzer < itis.sql
```

6. To install basic database schema run prepared migrations:

```
$ cd <project path>
$ php utils/updatedb.php
```

7. Import desired data (DB entries as well as the underlying images) into the system.
   There is `utils/import.php` for this task.
    
8. After changing organisms (adding or removing) the organism distance script should be executed:

```
$ php utils/update-organism-distances.php 
```

For convenience there is a `deploy.php` script which does this step in order to make things easy (except importing data)

Running
=======

Use Apache or PHP embedded server to serve `www` directory with `index.php` as index file:

```
php -S localhost:8000 -t <project path>/www
```

Structure
=========

TBD

Developing
==========

Run `gulp in development mode (rebuilds everything and watch for changes):

```
$ gulp development
```