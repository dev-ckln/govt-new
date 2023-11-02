
set -x

DB=raggupta_gs


if mysqldump -u root -p $DB  --ignore-table=$DB.cache_config   --ignore-table=$DB.cache_container   --ignore-table=$DB.cache_data --ignore-table=$DB.cache_default --ignore-table=$DB.cache_discovery    --ignore-table=$DB.cache_dynamic_page_cache   --ignore-table=$DB.cache_entity --ignore-table=$DB.cache_menu --ignore-table=$DB.cache_render  --ignore-table=$DB.cache_toolbar   > /tmp/$DB.sql
then
d=`date|tr " :" --`
pwd=`pwd`
BASEDIR=`basename $pwd`
file="govtschemes-$d"
echo "file=$file  d=$BASEDIR"
gzfile=/home/raggupta/backups/s3/$file.tar.gz
if tar cvzf $gzfile --exclude=logs  .  -C /tmp/ $DB.sql
then
echo "Successfully backed up in :$gzfile"
else
echo "Backup failed"
fi

fi


