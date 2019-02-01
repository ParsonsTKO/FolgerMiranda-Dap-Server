bin/console doctrine:database:drop --force
bin/console doctrine:generate:entities AppBundle --no-backup
bin/console doctrine:database:create
bin/console doctrine:schema:update --force --complete --dump-sql
