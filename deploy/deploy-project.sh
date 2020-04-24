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
        ["parameters.database_host"]=${POSTGRES_DATABASE_IP_ADDRESS}
        ["parameters.database_name"]=${PROJECT_NAME}
        ["parameters.database_port"]=${POSTGRES_DATABASE_PORT}
        ["parameters.database_user"]=${PROJECT_NAME}
        ["parameters.database_password"]=${POSTGRES_DATABASE_PASSWORD}
        ["parameters.elasticsearch_host"]=${ELASTICSEARCH_URL}
        ["parameters.mailer_host"]='shopmail.shopsys.cz'
        ["parameters.trusted_proxies[+]"]=10.0.0.0/8
        ["parameters.gtm_config.cs.container_id"]="${GTM_CONTAINER_ID_CS}"
        ["parameters.gtm_config.sk.container_id"]="${GTM_CONTAINER_ID_SK}"
        ["parameters.gtm_config.en.container_id"]="${GTM_CONTAINER_ID_EN}"
        ["parameters.disable_form_fields_from_transfer"]=${DISABLE_FORM_FIELDS_FROM_TRANSFER}
    )

    declare -A ENVIRONMENT_VARIABLES=(
        ["S3_API_HOST"]=${S3_API_HOST}
        ["S3_API_USERNAME"]=${S3_API_USERNAME}
        ["S3_API_PASSWORD"]=${S3_API_PASSWORD}
        ["S3_API_BUCKET_NAME"]=${PROJECT_NAME}
        ["REDIS_PREFIX"]=${PROJECT_NAME}
        ["ELASTIC_SEARCH_INDEX_PREFIX"]=${PROJECT_NAME}
   )

    VARS=(
        TAG
        PROJECT_NAME
        BASE_PATH
        APP_CONFIG_DIRECTORY
    )

    source "${DEPLOY_TARGET_PATH}/functions.sh"
    source "${DEPLOY_TARGET_PATH}/parts/parameters.sh"
    source "${DEPLOY_TARGET_PATH}/parts/domains.sh"
    source "${DEPLOY_TARGET_PATH}/parts/environment-variables.sh"
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
