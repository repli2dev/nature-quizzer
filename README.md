# Nature Quizzer

Simple web-based adaptive learning system for teaching users biological concepts such as plants, animals,...

## Dependencies

General:

 - PHP >= 7.4
 - PostgreSQL >= 12
 - Node.js and its dependencies captured in `package.json`
 - Composer and its dependencies captured in `composer.json`

##  Installation

0. Update to proper selected GIT branch/tag.
   master branch = development
   production branch = production

1. Consult the `.env` configuration to ensure that path to `nature-quizzer-packages` is correct and especially that `IP` variable is correctly set.  
   On local environment also edit `/etc/hosts` file to contain `<IP> poznavackaprirody.test www.poznavackaprirody.test` entry.

2. When in local environment: Run Docker composition with `make up` (first time) or `make start` (afterwards) all containers (nginx, db, php, adminer) will come up:
   - `<IP>:88` with Adminer, you can use `nature-quizzer` as username and password as well as `postgres`.
   - `poznavackaprirody.test` and `www.poznavackaprirody.test` on which this application is made work.
   - all subsequent commands should be executed either from `php` container (`make ssh php`) or `node container` (`make ssh node`).

3. Set proper permissions to `temp` and `log` directory:

```
$ chmod -R 777 log temp
```

4. Install development dependencies via `node`:

```
$ cd <project path>
$ npm install
```

5. Install runtime (PHP) dependencies via `composer`:
 
```
$ cd <project path>
$ composer install
```

6. Run `gulp` to build css, templates and javascript files:

```
$ cd <project path>
$ gulp
```

There are two gulp targets: `gulp development` will watch for changes, whereas `gulp production` will minify the JS output. 

7. Create local configuration (database credentials, FB and Google+ API keys etc)

```
$ cd <project path>
$ vim app/config/config.local.neon
```

8. Create database and import ``sources/col.sql.gz`` from ``nature-quizzer-packages`` repository into `col` schema.
 
```
$ psql -U <user> -d nature-quizzer < col.sql
$ psql -U <user> -d nature-quizzer < col.customization.sql
```

For local environment there are `make import-database` and `make import-col` targets prepared.

9. To install basic database schema run prepared migrations:

```
$ cd <project path>
$ php utils/updatedb.php
```

10. Import desired data (DB entries as well as the underlying images) into the system.
   There is `utils/package.php` for this task.
    
11. After changing organisms (adding or removing) the organism distance script should be executed:

```
$ php utils/update-organism-distances.php 
```

Also check that all available organisms has any entry in `organism_distance` table, otherwise the system will be behave
unexpectedly:

``
SELECT * FROM organism 
LEFT JOIN organism_distance AS od1 ON organism.id_organism = od1.id_organism_to
LEFT JOIN organism_distance AS od2 ON organism.id_organism = od2.id_organism_from
WHERE od1.id_organism_to IS NULL OR od2.id_organism_from IS NULL
``

For convenience there is a `deploy.php` script which does some of this steps in order to make things easy
(except importing data), especially for update. Mostly relevant for production environment when deploying as local environment needs only some steps during 
development.

## Data packages

Prepared data packages for import are located in standalone repository:
https://github.com/repli2dev/nature-quizzer-packages

For creating new topic see /staging/HOWTO.md.

## Running locally

Use prepared docker composition, see instructions above in Installation section.

## Structure

The application is divided into client-side application (in browser only) and API providing only the data.
The front page of client-side application is served from server. The administration is also implemented in classic
way of server-rendered webpages.

The structure copies typical structure of Nette framework applications.

```
├── app
│   ├── .db-data									Local environment PostgreSQL datadir
│   ├── .docker									Local environment (Docker) configuration
│   ├── config									Configuration(s) of the application, should be publicly available!
│   ├── grids
│   ├── presenters
│   └── templates								Templates of administration and front-page.
│       ├── Admin
│       ├── Concept
│       ├── Diagnostic
│       ├── Error
│       ├── Group
│       ├── Homepage							The front-page, basic layout of whole page.
│       ├── Model
│       ├── Organism
│       └── components
├── frontend									Contains all parts of client-side application (later processed by Gulp).
│   ├── css										Styling of client-side application and administration as well.
│   ├── js										Parts of Ember application as well as external libraries.
│   ├── locales									Translations to different languages.
│   └── templates								HTMLBars templates of client-side application.
├── library
│   ├── database								Classes for accessing and manipulating the database.
│   │   ├── model
│   │   └── utils
│   ├── logging
│   ├── model									Classes of student model and instructional policy
│   │   ├── entries
│   │   └── utils
│   ├── processors								Classes for processing parts of API requests (such as answer,...)
│   ├── router
│   ├── runtime									Classes for determing current user, langugage, round, client.
│   ├── tools									Various classes used for data retrieval (EOL, CurlDownloader, WebProcessor...)
│   └── utils
├── log											Folders of logging warning, errors and exceptions. Must be writable!
├── node_modules								External dependencies managed by npm.
├── resources
│   ├── migrations								Database migrations, see part Developing.
│   └── scheme									
│       └── functions
├── staging										Folder for preparing new topics
├── temp										Folder for caches, sessions etc. Must be writable!
├── utils										Various scripts for managing the system, see part Developing.
├── vendor										External dependencies managed by Composer.
└── www
    ├── css										Gulp compiled css styles.
    ├── images
    │   ├── organisms							Images of organisms's representations.
    │   └── showcases							Images for Open Graph API (used when sharing page via Facebook).
    └── src										Gulp compiled javascripts.
```

## Developing

When doing changes in any JS/CSS/templates/locales ensure to run `gulp` in development mode
(for rebuilding everything and watch for changes):

```
$ gulp development
```

### Client-side application

The sources are located in /frontend folder. The application uses typical Ember.js stack. 

### API

API requests goes throw /api/<request> which are operated by /app/presenters/ApiPresenter.php which delegates work to
/library/processors/...

### Administration

Administration is standard Nette server-side (rendered) application living in /app folder.

### Student model and Instructional Policy

The cornerstone of this adaptive system is located in /library/model/ folder (NatureQuizzer\Model namespace).

The model is currently divided (theoretically) into two parts:
 - Student model - ensuring modeling of student skills, organism difficulty and prior knowledge of students.
 - Instructional Policy - choosing based on student model which organisms practice now.

This layout is refined into IModelFacade methods get() and answer() and later implemented by BasicElo class which have
now two descentants, one with random distractors and one with taxonomy distractors.

Each model has its ID, which is introduced also into database table model. Moreover, each user has associated model on
its creation which is used in subsequent user uses of the application.

Warning: when changing student model, beware of interconnection with `/utils/basic-elo.php` script, which should behave consistently.

### Database migration

As it is highly probable that there will be changes in database schema, the database migration tool was implemented.
The migrations itself are in /resources/migrations. They must follow the 007_NAME.sql syntax with three digit number
ordered by the order of execution.

Migration are performed when using /utils/updatedb.php or automatically when using /deploy.php tool

Successfully executed migrations are stored into database table `meta_migrations`. When at least of them is unsuccessful,
all of currently new are rollbacked. In that case manual fix of the migration files is needed.

### Package manager

As the content needs some constant care the package manager was implemented. It can take care about importing:

 - laguages,
 - groups,
 - organisms, their representations and their topics assignation.

Topic (= concept) is one set of organisms which can practiced by student.
                  (In fact topics is assignation of particular organisms to some name.)

Package is a bunch of data items meant to be imported together and can contain multiple topics.
        Example: Czech animals contains all languages, groups and organism for topic Czech animals and Czech mammals...

For package management there is /utils/package.php script with takes care about importing them and garbage collection
of unused data items (and files)

For more details about packages, see /staging/HOWTO.md.

### Basic ELO

When changing parameters of the student model ensure that that the model is recalculate.
There is tool in /utils/basic-elo.php.

### Backuping database

For convenience simple backup tool was introduced. The backup tool located in `/utils/backup.php` can create backup of
database only or even with representations, restore option is also available.
The stored backup is stored into one compressed file in folder according to production instance.

### Catalogue of Life

In earlier versions there was a ITIS taxonomy, however with adding mushrooms replacement was needed.
Catalogue of Life was selected, however it was too large, so data were exported with this query:

```sql
CREATE TABLE taxon_exported AS SELECT
	"taxonID",
	"taxonomicStatus", 
	"acceptedNameUsageID",
	"parentNameUsageID",
	COALESCE(NULLIF(CONCAT_WS(' ', "genericName", "specificEpithet", "infraspecificEpithet"), ''), "scientificName") AS "completeName", 
	"scientificName",
	"genericName", 
	"specificEpithet",
	"infraspecificEpithet"
FROM "taxon"
```

After export add indexes on `taxonID`, `acceptedNameUsageID`, `parentNameUsageID` and `genericName`, also 
create foreign key on `acceptedNameUsageID` and `parentNameUsageID` to `taxonID`.

When exporting the result into SQL file, do not forget to create schema `col` if it doesn't exist and strip owner information.
