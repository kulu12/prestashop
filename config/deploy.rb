set :application, "xxx"
set :repository,  "git@github.com:Pajk/prestashop.git"

set :scm, :git

role :web, "1.1.1.1"
role :app, "1.1.1.1"
role :db,  "1.1.1.1", :primary => true
role :db,  "1.1.1.1"

set :use_sudo, false

set :deploy_to, "/home/xxx/app/"
set :deploy_via, :remote_cache

set :user, "xxx"
set :password, "yyy"

after "deploy:update_code", :roles => [:app, :db] do
  run "cp #{shared_path}/config/settings.inc.php #{release_path}/config/settings.inc.php"
  run "rm -rf #{release_path}/install"
  run "rm -r  #{release_path}/log"
  run "ln -nfs #{shared_path}/log #{release_path}/log"
  run "rm -r  #{release_path}/tools/smarty/compile"
  run "ln -nfs #{shared_path}/compile #{release_path}/tools/smarty/compile"
  run "rm -r  #{release_path}/tools/smarty/cache"
  run "ln -nfs #{shared_path}/cache #{release_path}/tools/smarty/cache"
  run "rm -r  #{release_path}/tools/smarty_v2/compile"
  run "ln -nfs #{shared_path}/compile_v2 #{release_path}/tools/smarty_v2/compile"
  run "rm -r  #{release_path}/tools/smarty_v2/cache"
  run "ln -nfs #{shared_path}/cache_v2 #{release_path}/tools/smarty_v2/cache"
  run "rm -r  #{release_path}/themes/prestashop/lang"
  run "ln -nfs #{shared_path}/themes_lang #{release_path}/themes/prestashop/lang"
  run "rm -r  #{release_path}/themes/prestashop/cache"
  run "ln -nfs #{shared_path}/themes_cache #{release_path}/themes/prestashop/cache"
  run "rm -r  #{release_path}/mails"
  run "ln -nfs #{shared_path}/mails #{release_path}/mails"
  run "rm -r  #{release_path}/upload"
  run "ln -nfs #{shared_path}/upload #{release_path}/upload"
  run "rm -r  #{release_path}/download"
  run "ln -nfs #{shared_path}/download #{release_path}/download"
  run "rm -r  #{release_path}/img"
  run "ln -nfs #{shared_path}/img #{release_path}/img"
  
end

namespace :deploy do
  task :start do ; end
  task :stop do ; end
  task :restart, :roles => :app, :except => { :no_release => true } do
    run "#{try_sudo} touch #{File.join(current_path,'tmp','restart.txt')}"
  end
end