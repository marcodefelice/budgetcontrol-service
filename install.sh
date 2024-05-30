#!/bin/bash

# Imposta l'ambiente di default
env="dev"

# Funzione per mostrare l'uso del comando
usage() {
  echo "Usage: $0 [-e environment | --env environment]"
  echo "Environments: dev, local, prod"
  exit 1
}

# Parsing degli argomenti della riga di comando
while [[ "$#" -gt 0 ]]; do
  case $1 in
    -e|--env)
      if [ -n "$2" ] && [[ $2 != -* ]]; then
        env="$2"
        shift 2
      else
        echo "Error: Missing value for $1"
        usage
      fi
      ;;
    *)
      echo "Error: Unknown parameter: $1"
      usage
      ;;
  esac
done

# Mostra l'ambiente selezionato
echo "Installing $env environment"

# Aggiungi logica per ambiente specifico
case $env in
  dev)
    echo "Setting up DEV environment"
    docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
    docker container cp bin/apache/dev-api.budgetcontrol.dev.conf budgetcontrol-core:/etc/apache2/sites-available/budgetcontrol.cloud.conf
    ;;
  local)
    echo "Setting up LOCAL environment"
    docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
    docker container cp bin/apache/dev-api.budgetcontrol.local.conf budgetcontrol-core:/etc/apache2/sites-available/budgetcontrol.cloud.conf
    ;;
  prod)
    echo "Setting up PROD environment"
    docker-compose -f docker-compose.yml up -d
    docker container cp bin/apache/api.budgetcontrol.cloud.conf budgetcontrol-core:/etc/apache2/sites-available/budgetcontrol.cloud.conf
    ;;
  *)
    echo "Unknown environment: $env"
    usage
    ;;
esac

docker container exec budgetcontrol-core service apache2 restart

echo "Build Gateway"
docker container cp microservices/Gateway/bin/apache/default.conf budgetcontrol-gateway:/etc/apache2/sites-available/budgetcontrol.cloud.conf
docker container exec budgetcontrol-gateway service apache2 restart

echo "Build ms Authentication"
docker container cp microservices/Authentication/bin/apache/default.conf budgetcontrol-ms-authentication:/etc/apache2/sites-available/budgetcontrol.cloud.conf
docker container exec budgetcontrol-ms-authentication service apache2 restart

echo "Build ms Workspace"
docker container cp microservices/Workspace/bin/apache/default.conf budgetcontrol-ms-workspace:/etc/apache2/sites-available/budgetcontrol.cloud.conf
docker container exec budgetcontrol-ms-workspace service apache2 restart

echo "Build ms Stats"
docker container cp microservices/Stats/bin/apache/default.conf budgetcontrol-ms-stats:/etc/apache2/sites-available/budgetcontrol.cloud.conf
docker container exec budgetcontrol-ms-stats service apache2 restart

echo "Build ms Budget"
docker container cp microservices/Budget/bin/apache/default.conf budgetcontrol-ms-budget:/etc/apache2/sites-available/budgetcontrol.cloud.conf
docker container exec budgetcontrol-ms-budget service apache2 restart

echo "Build ms Entries"
docker container cp microservices/Entries/bin/apache/default.conf budgetcontrol-ms-entries:/etc/apache2/sites-available/budgetcontrol.cloud.conf
docker container exec budgetcontrol-ms-entries service apache2 restart

echo "Build ms Wallets"
docker container cp microservices/Wallets/bin/apache/default.conf budgetcontrol-ms-wallets:/etc/apache2/sites-available/budgetcontrol.cloud.conf
docker container exec budgetcontrol-ms-wallets service apache2 restart

echo "Build ms Search Engine"
docker container cp microservices/SearchEngine/bin/apache/default.conf budgetcontrol-ms-searchengine:/etc/apache2/sites-available/budgetcontrol.cloud.conf
docker container exec budgetcontrol-ms-searchengine service apache2 restart

echo "Install composer and run migrations"
docker exec budgetcontrol-core composer install
docker exec budgetcontrol-core php artisan migrate
docker exec budgetcontrol-core php artisan optimize

docker exec budgetcontrol-gateway composer install
docker exec budgetcontrol-gateway php artisan optimize

docker exec budgetcontrol-ms-stats composer install
docker exec budgetcontrol-ms-authentication composer install
docker exec budgetcontrol-ms-jobs composer install
docker exec budgetcontrol-ms-workspace composer install
docker exec budgetcontrol-ms-budget composer install
docker exec budgetcontrol-ms-entries composer install
docker exec budgetcontrol-ms-wallets composer install
docker exec budgetcontrol-ms-searchengine composer install

echo "All done! Enjoy"
