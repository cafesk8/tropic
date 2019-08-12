# Migration instruction

Data are migrated by direct access to database, where they are stored.
This solution is flexible for changes, we don't need to use some
additional step for accessing data (e.g. export data to CSV).

**Copy of the production database on Bushman CI**

Mysql server has been installed on Bushman CI. Copy of the production DB was imported
into database called `dbsklad` (name was inspired by production name)

This solution allowed us to simulate connection to remote DB. In `parameters.yml.dist`
are stored credentials to connect to that DB.

**You can run it locally as well if you want**

If you want to run migration DB locally, some steps need to be done for the first time.

1. Download DB dump from [gDrive](https://drive.google.com/open?id=1hs3gjRCqfS6NTpBx3O7guDqQpPZF_LpQ) to `./migration-data`
2. Update docker-compose.yml, add mysql docker image (copy from proper file base on your OS, e.g. `./docker/conf/docker-compose.yml.dist`)
3. Rebuild docker-compose
4. Access mysql docker image by `docker-compose exec mysql bash`
5. Run `zcat ./migration-data/db23171_sklad.sql.gz | mysql -u 'root' -p dbsklad`
6. Enter password `root`
7. DB dump will be imported. You can check it with adminer.

**Migration commands**

All commands for migrations should be located in namespace `Shopsys\ShopBundle\Command\Migration`.
Commands in this namespace are autowired with a connection to MySql database automatically.  
You don't need to register that manually.

**Commands for migration, in order they need to be run**

To run commands, you need them from terminal on server. Then you need to run commands in order they are
listed bellow:

1. Migrate product data: `php bin/console shopsys:migrate:product-data`
2. Migrate product images: `php bin/console shopsys:migrate:product-images`
