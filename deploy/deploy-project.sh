#!/bin/bash -e

BASE_PATH="$(realpath "$(dirname "$0")/..")"
CONFIGURATION_TARGET_PATH="${BASE_PATH}/var/deployment/kubernetes"
BASIC_AUTH_PATH="${BASE_PATH}/deploy/basicHttpAuth"
DEPLOY_TARGET_PATH="${BASE_PATH}/var/deployment/deploy"
APP_CONFIG_DIRECTORY="config"
APP_CONFIG_PATH="${BASE_PATH}/${APP_CONFIG_DIRECTORY}"

function deploy() {
    DOMAINS=(
        DOMAIN_HOSTNAME_1
        DOMAIN_HOSTNAME_2
        DOMAIN_HOSTNAME_3
    )

    declare -A PARAMETERS=(
        ["parameters.database_name"]=${POSTGRES_DATABASE_NAME}
        ["parameters.database_port"]=${POSTGRES_DATABASE_PORT}
        ["parameters.database_user"]=${POSTGRES_DATABASE_USER}
        ["parameters.database_password"]=${POSTGRES_DATABASE_PASSWORD}
        ["parameters.elasticsearch_host"]='elasticsearch'
        ["parameters.mailer_host"]='shopmail.shopsys.cz'
        ["parameters.trusted_proxies[+]"]=10.0.0.0/8
    )

    VARS=(
        POSTGRES_DATABASE_IP_ADDRESS
        ELASTICSEARCH_IP_ADDRESS_HOST
        ELASTICSEARCH_HOST_PORT
        TAG
        PROJECT_NAME
        BASE_PATH
        APP_CONFIG_DIRECTORY

        S3_API_HOST
        S3_API_USERNAME
        S3_API_PASSWORD
        S3_API_BUCKET_NAME

        REDIS_PREFIX
        ELASTIC_SEARCH_INDEX_PREFIX
    )

    source "${DEPLOY_TARGET_PATH}/functions.sh"
    source "${DEPLOY_TARGET_PATH}/parts/parameters.sh"
    source "${DEPLOY_TARGET_PATH}/parts/domains.sh"
    source "${DEPLOY_TARGET_PATH}/parts/kubernetes-variables.sh"
    source "${DEPLOY_TARGET_PATH}/parts/deploy.sh"
}

function merge() {
    source "${BASE_PATH}/vendor/devops/kubernetes-deployment/deploy/functions.sh"
    merge_configuration
}

case "$1" in
    "deploy")
        deploy
        ;;
    "merge")
        merge
        ;;
    *)
        echo "invalid option"
        exit 1
        ;;
esac
