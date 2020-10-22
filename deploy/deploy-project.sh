#!/bin/bash -e

BASE_PATH="$(realpath "$(dirname "$0")/..")"
CONFIGURATION_TARGET_PATH="${BASE_PATH}/var/deployment/kubernetes"
BASIC_AUTH_PATH="${BASE_PATH}/deploy/basicHttpAuth"
DEPLOY_TARGET_PATH="${BASE_PATH}/var/deployment/deploy"

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
        ["parameters.pohoda_company_ico"]="${POHODA_COMPANY_ICO}"
        ["parameters.pohoda_mserver_url"]="${POHODA_MSERVER_URL}"
        ["parameters.pohoda_mserver_port"]="${POHODA_MSERVER_PORT}"
        ["parameters.pohoda_mserver_password"]="${POHODA_MSERVER_PASSWORD}"
        ["parameters.pohoda_mserver_login"]="${POHODA_MSERVER_LOGIN}"
        ["parameters.pohoda_database_name"]="${POHODA_DATABASE_NAME}"
        ["parameters.google_translate_enabled"]=${GOOGLE_TRANSLATE_ENABLED}
        ["parameters.ecomail_api_key"]="${ECOMAIL_API_KEY}"
        ["parameters.ecomail_list_id"]="${ECOMAIL_LIST_ID}"
        ["parameters.ecomail_list_id_sk"]="${ECOMAIL_LIST_ID_SK}"
        ["parameters.balikobot.username"]="${BALIKOBOT_USERNAME}"
        ["parameters.balikobot.apiKey"]="${BALIKOBOT_APIKEY}"
        ["parameters.zbozi_shop_id"]="${ZBOZI_SHOP_ID}"
        ["parameters.zbozi_private_key"]="${ZBOZI_PRIVATE_KEY}"

        # GoPay
        ["parameters.gopay_config.cs.goid"]="${GOPAY_CONFIG_GOID_CS}"
        ["parameters.gopay_config.cs.clientId"]="${GOPAY_CONFIG_CLIENT_ID_CS}"
        ["parameters.gopay_config.cs.clientSecret"]="${GOPAY_CONFIG_CLIENT_SECRET_CS}"
        ["parameters.gopay_config.sk.goid"]="${GOPAY_CONFIG_GOID_SK}"
        ["parameters.gopay_config.sk.clientId"]="${GOPAY_CONFIG_CLIENT_ID_SK}"
        ["parameters.gopay_config.sk.clientSecret"]="${GOPAY_CONFIG_CLIENT_SECRET_SK}"
        ["parameters.gopay_config.en.goid"]="${GOPAY_CONFIG_GOID_EN}"
        ["parameters.gopay_config.en.clientId"]="${GOPAY_CONFIG_CLIENT_ID_EN}"
        ["parameters.gopay_config.en.clientSecret"]="${GOPAY_CONFIG_CLIENT_SECRET_EN}"
        ["parameters.gopay_config.isProductionMode"]=${GOPAY_CONFIG_IS_PRODUCTION_MODE}

        # Cofidis
         ["parameters.cofidis_config.merchant_id"]="${COFIDIS_CONFIG_MERCHANT_ID}"
         ["parameters.cofidis_config.login"]="${COFIDIS_CONFIG_LOGIN}"
         ["parameters.cofidis_config.cofisun_pass"]="${COFIDIS_CONFIG_COFISUN_PASS}"
         ["parameters.cofidis_config.inbound_pass"]="${COFIDIS_CONFIG_INBOUND_PASS}"
         ["parameters.cofidis_config.outbound_pass"]="${COFIDIS_CONFIG_OUTBOUND_PASS}"
         ["parameters.cofidis_config.isProductionMode"]=${COFIDIS_CONFIG_IS_PRODUCTION_MODE}
    )

    declare -A ENVIRONMENT_VARIABLES=(
        ["S3_API_HOST"]=${S3_API_HOST}
        ["S3_API_USERNAME"]=${S3_API_USERNAME}
        ["S3_API_PASSWORD"]=${S3_API_PASSWORD}
        ["S3_API_BUCKET_NAME"]=${PROJECT_NAME}
        ["REDIS_PREFIX"]=${PROJECT_NAME}
        ["ELASTIC_SEARCH_INDEX_PREFIX"]=${PROJECT_NAME}
        ["CDN_DOMAIN"]=${CDN_DOMAIN}
   )

    VARS=(
        TAG
        PROJECT_NAME
        BASE_PATH
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
    source "${BASE_PATH}/vendor/shopsys/cdn/deploymentPatch/cdnPatch.sh"
    source "${BASE_PATH}/deploy/parts/gopay_ip_addresses_allow.sh"
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
