# AtoM Migrations

Given that migrations are “one-off” projects they don’t have to be done in
a specific way, but here’s some rough PHP-centric guidance on one way to do
them.

## Preparation

Before starting migration development a place should be propared for
migration-related files and client source data should be checked for
obvious errors.

### Conversion

Source data should usually be converted to CSV.

The Gnumeric spreadsheet application comes with a good command - ssconvert -
for converting Excel files to CSV.

See: https://stackoverflow.com/questions/10557360/convert-xlsx-to-csv-in-linux-with-command-line

### Organizing migration files

Migration scripts and data are most often stored in an Artefactual hosted
repository named with the convention `client/<organization name>/scripts`. This
repository can hold organization-specific scripts, migrations, data, etc.

Creating a “migration” directory in this repo is good practice as it will
keep files related to the current migration separated from files for
subsequent migrations, client-specific scripts, etc.

Within the “migration” directory of the repo a “sourcedata” directory can
be created to store client data in. Client data for a migration should,
ideally, be exported in a format as similar to AtoM’s CSV import formats as
possible. When getting updated versions of source data files it can be helpful
to append some kind of version number to them so they don't get confused with
earlier versions.

### Preflighting client data

AtoM's CSV check task can be used to detect some errors in client source files.

For example:

    $ php symfony csv:check migration/sourcedata/fonds/fonds.csv

## Development

### Transformation

Our usual practice is to not alter client data directly but to transform it
into CSV files that can be directly imported into AtoM.

Transformations usually take CSV data (either provided by the client directly
or exported from Excel), manipulate each row, and write it to a new file.

Within the “migration” directory of the repo a “transform” directory can be
created to store files that handle the CSV transformation in. An “output”
directory can be created to store the CSV output of the transformations.

These files can either be PHP files that work with AtoM's `csv:custom-import`
Symfony task or ad-hoc scripts in PHP, Python, etc.

Files that work with AtoM’s `csv:custom-import` Symfony task should end with
a return statement that returns an instance of a QubitCsvTransform instance,
either created from scratch or, more commonly, created by a
QubitCsvTransformFactory instance.

The `QubitCsvTransformFactory` class:

- Optionally converts Windows-1252 encoded data into UTF-8
- Optionally sorts row data, before it’s output as CSV, by level of
  description (highest level to lowest level) or a developer-defined sorting
  criteria
- Writes transformed CSV output in a series of files with a fixed number rows
  each (1000 by default) in order to avoid running out of memory during large
  imports can conditionally ignore rows

Some documentation of these classes exists
(https://wiki.accesstomemory.org/Resources/CSV_transformation) but it's likely
worth looking at examples in past migrations to get a sense of how they work.

Here's an example of an unusually simple QubitCsvTransformFactory-created
transformation (to show the basic structure): https://bit.ly/3yandax

### Custom Scripts

Often migrations require the use of custom scripts. These scripts may do things
like sequence steps in the migration or import AtoM data, like donors and
physical objects, for which no dedicated import tool yet exists (although donor
and physical object data can both be imported as related data to accessions and
descriptions respectively).

Within the “migration” directory of the repo a “scripts” directory can be
created to store migration scripts in. If there is a script that should be run
to import migrated data then mentioning it in a README file in the
“migration” directory of the repo will help anyone revisiting the migration
in the future to make sense of it.

### Script Optimization

If a custom import script runs very slow there are a few optimizations to try:

- Disable Elasticsearch indexing, if applicable, when saving an AtoM object
- Disable nested set building when saving an AtoM object

Here’s an example of doing both of the above while assigning a new parent ID
to an existing description::

```php
$i = QubitInformationObject::GetById($id);
$i->disableNestedSetUpdating = true;
$i->indexOnSave = false;
$i->parentId = $newParentId;
$i->save();
```

**NOTE:** If disabling nested set updating, be sure to run the
`propel:build-nested-set` task afterwards.

Aside from the above, pre-caching table data into memory can help scripts run
faster by minimizing the number of database queries done.

## Importing

Once development is roughed out the import can be fully run on a test server
for internal or client QA.

### Sequencing the import

Adding a Bash script, `full_import.sh` for example, at the root directory of
the migration directory allows you to automate and sequence your import.

### Settings

Settings, such as default template, can be set using the `tools:settings` task.

Example:

    $ php symfony tools:settings --scope="default_template" set informationobject rad

### Keymap data

Here’s an example of importing a single file of description data:

    $ php symfony csv:import --source-name="items" migration/output/items/items_0000.csv`

Note the use of the `--source-name` command-line option. This helps keep track
of imported data by assigning what is called a “source name” to imported
data. When using most import tools each item imported into AtoM gets noted in a
“keymap” table in the AtoM database. The keymap table records the “source
ID” (the ID, if any, in the “legacyId” column of the imported CSV data), the
“target ID” (the AtoM ID of the imported item),  the “source name” (a name
to describe the imported data), and the “target name” (the AtoM data type of
the imported item). Keymap data can be queried in MySQL to find out a number of
things, like the AtoM ID of an imported item or the count of how many items
were imported from a given source.

### Memory limitations

Some imports will exhaust the amount of memory allocated to PHP. In order to
ignore the default limit, and use any memory available, an import can be run
like this:

    $ php -d memory_limit=-1 symfony csv:import --source-name="items" migration/output/items/items_0000.csv`

### Timing imports

The Unix `time` command is useful for determining how long an import takes to
run. The `-v` option provides verbose timing output, including "Elapsed (wall
clock) time".

    $ time -v php symfony csv:import --source-name="items" migration/output/items/items_0000.csv

### Import sequencing

Lower-level data, like terms and actors, should be imported before higher level
data, like information objects.

It can be helpful to create a shell script to sequence the import. In this
script it can be useful to note, in comments, how long each step of the import
takes to run.

## Troubleshooting

Given that migration issues can be complicated issues inevitably arise.

### Detecting missing data

To ensure that all source data got imported it can be useful to compare
counts of source data with the number of imported items.

The `scripts/data_counts.php` script can be used to generate counts.

### Dealing with migration issues

For complicated imports use of a tool like Trello can help keep track of any
issues found.

For complex transformations of data it can be worth describing things using
pseudocode so non-developers can vet what's being done with source data.

### Importing in phases

If there are parts of an import that aren't thoroughly tested it can be useful
to pause the import after these parts and test that they worked. In order to do
this rather than writing one shell script to sequence the import multiple shell
scripts can be written so importing can be done in "phases".
