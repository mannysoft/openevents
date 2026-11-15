@servers(['live' => ['root@3.10.91.85']])

@task('live', ['on' => 'live'])
    cd /home/admin/web/globalwomanevents.com/public_html
    pwd
    git pull
    #composer update
    php artisan migrate
@endtask