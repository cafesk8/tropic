#!/bin/sh -ex

# Login to Docker Hub for pushing images into register
echo ${DOCKER_PASSWORD} | docker login --username ${DOCKER_USERNAME} --password-stdin

# Create unique docker image tag with commit hash
DOCKER_IMAGE_TAG=${APPLICATION_ENVIRONMENT}-commit-${GIT_COMMIT}

## Docker image for application php-fpm container
docker image pull ${APPLICATION_IMAGE_NAME}:${DOCKER_IMAGE_TAG} || (
    echo "Image not found (see warning above), building it instead..." &&
    docker image build \
        --tag ${APPLICATION_IMAGE_NAME}:${DOCKER_IMAGE_TAG} \
        --target production \
        -f docker/php-fpm/Dockerfile \
        . &&
    docker image push ${APPLICATION_IMAGE_NAME}:${DOCKER_IMAGE_TAG}
)

# Create real parameters files to be modified and applied to the cluster as configmaps
cp app/config/domains_urls.yml.dist app/config/domains_urls.yml
cp app/config/parameters.yml.dist app/config/parameters.yml

# Replace docker images for php-fpm of application
yq write --inplace kubernetes/deployments/webserver-php-fpm.yml spec.template.spec.containers[0].image ${APPLICATION_IMAGE_NAME}:${DOCKER_IMAGE_TAG}
yq write --inplace kubernetes/deployments/webserver-php-fpm.yml spec.template.spec.initContainers[0].image ${APPLICATION_IMAGE_NAME}:${DOCKER_IMAGE_TAG}

yq write --inplace kubernetes/kustomize/overlays/first-deploy/webserver-php-fpm-patch.yml [0].value.image ${APPLICATION_IMAGE_NAME}:${DOCKER_IMAGE_TAG}
yq write --inplace kubernetes/kustomize/overlays/first-deploy/webserver-php-fpm-patch.yml [0].value.image ${APPLICATION_IMAGE_NAME}:${DOCKER_IMAGE_TAG}

yq write --inplace kubernetes/kustomize/overlays/continuous-deploy/webserver-php-fpm-patch.yml [0].value.image ${APPLICATION_IMAGE_NAME}:${DOCKER_IMAGE_TAG}
yq write --inplace kubernetes/kustomize/overlays/continuous-deploy/webserver-php-fpm-patch.yml [0].value.image ${APPLICATION_IMAGE_NAME}:${DOCKER_IMAGE_TAG}

yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.containers[0].image ${APPLICATION_IMAGE_NAME}:${DOCKER_IMAGE_TAG}
yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.initContainers[0].image ${APPLICATION_IMAGE_NAME}:${DOCKER_IMAGE_TAG}
yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.initContainers[1].image ${APPLICATION_IMAGE_NAME}:${DOCKER_IMAGE_TAG}

# Set domain name into ingress controller so ingress can listen on domain name
yq write --inplace kubernetes/ingress.yml spec.rules[0].host ${DOMAIN_HOSTNAME_1}
yq write --inplace kubernetes/ingress.yml spec.rules[1].host ${DOMAIN_HOSTNAME_2}
yq write --inplace kubernetes/ingress.yml spec.rules[2].host ${DOMAIN_HOSTNAME_3}

# Set domain name into ingress controller for tls to pass proper certificate
yq write --inplace kubernetes/kustomize/base/ingress-patch.yaml spec.tls[0].hosts[+] ${DOMAIN_HOSTNAME_1}
yq write --inplace kubernetes/kustomize/base/ingress-patch.yaml spec.tls[0].hosts[+] ${DOMAIN_HOSTNAME_2}
yq write --inplace kubernetes/kustomize/base/ingress-patch.yaml spec.tls[0].hosts[+] ${DOMAIN_HOSTNAME_3}

# Set domain into webserver hostnames
yq write --inplace kubernetes/deployments/webserver-php-fpm.yml spec.template.spec.hostAliases[0].hostnames[+] ${DOMAIN_HOSTNAME_1}
yq write --inplace kubernetes/deployments/webserver-php-fpm.yml spec.template.spec.hostAliases[0].hostnames[+] ${DOMAIN_HOSTNAME_2}
yq write --inplace kubernetes/deployments/webserver-php-fpm.yml spec.template.spec.hostAliases[0].hostnames[+] ${DOMAIN_HOSTNAME_3}

# set ip_addresses for external databases service endpoints
yq write --inplace kubernetes/endpoints/postgres.yml subsets[0].addresses[0].ip ${POSTGRES_DATABASE_IP_ADDRESS}
yq write --inplace kubernetes/endpoints/elasticsearch.yml subsets[0].addresses[0].ip ${ELASTICSEARCH_IP_ADDRESS_HOST}
yq write --inplace kubernetes/endpoints/elasticsearch.yml subsets[0].ports[0].port ${ELASTICSEARCH_HOST_PORT}
yq write --inplace kubernetes/services/elasticsearch.yml spec.ports[0].port ${ELASTICSEARCH_HOST_PORT}

# Add a mask for trusted proxies so that load balanced traffic is trusted and headers from outside of the network are not lost
yq write --inplace app/config/parameters.yml parameters.trusted_proxies[+] 10.0.0.0/8

# Set namespace for project
yq write --inplace kubernetes/namespace.yml metadata.name ${PROJECT_NAME}
yq write --inplace kubernetes/kustomize/base/kustomization.yaml namespace ${PROJECT_NAME}

# Set domain urls
yq write --inplace app/config/domains_urls.yml domains_urls[0].url https://${DOMAIN_HOSTNAME_1}
yq write --inplace app/config/domains_urls.yml domains_urls[1].url https://${DOMAIN_HOSTNAME_2}
yq write --inplace app/config/domains_urls.yml domains_urls[2].url https://${DOMAIN_HOSTNAME_3}

# set ENV variables into pods using php-fpm image
yq write --inplace kubernetes/deployments/webserver-php-fpm.yml spec.template.spec.containers[0].env[0].value ${S3_API_HOST}
yq write --inplace kubernetes/deployments/webserver-php-fpm.yml spec.template.spec.containers[0].env[1].value ${S3_API_USERNAME}
yq write --inplace kubernetes/deployments/webserver-php-fpm.yml spec.template.spec.containers[0].env[2].value ${S3_API_PASSWORD}
yq write --inplace kubernetes/deployments/webserver-php-fpm.yml spec.template.spec.containers[0].env[3].value ${S3_API_BUCKET_NAME}
yq write --inplace kubernetes/deployments/webserver-php-fpm.yml spec.template.spec.containers[0].env[4].value ${REDIS_PREFIX}
yq write --inplace kubernetes/deployments/webserver-php-fpm.yml spec.template.spec.containers[0].env[5].value ${ELASTIC_SEARCH_INDEX_PREFIX}

yq write --inplace kubernetes/kustomize/overlays/first-deploy/webserver-php-fpm-patch.yml [0].value.env[0].value ${S3_API_HOST}
yq write --inplace kubernetes/kustomize/overlays/first-deploy/webserver-php-fpm-patch.yml [0].value.env[1].value ${S3_API_USERNAME}
yq write --inplace kubernetes/kustomize/overlays/first-deploy/webserver-php-fpm-patch.yml [0].value.env[2].value ${S3_API_PASSWORD}
yq write --inplace kubernetes/kustomize/overlays/first-deploy/webserver-php-fpm-patch.yml [0].value.env[3].value ${S3_API_BUCKET_NAME}
yq write --inplace kubernetes/kustomize/overlays/first-deploy/webserver-php-fpm-patch.yml [0].value.env[4].value ${REDIS_PREFIX}
yq write --inplace kubernetes/kustomize/overlays/first-deploy/webserver-php-fpm-patch.yml [0].value.env[5].value ${ELASTIC_SEARCH_INDEX_PREFIX}

yq write --inplace kubernetes/kustomize/overlays/continuous-deploy/webserver-php-fpm-patch.yml [0].value.env[0].value ${S3_API_HOST}
yq write --inplace kubernetes/kustomize/overlays/continuous-deploy/webserver-php-fpm-patch.yml [0].value.env[1].value ${S3_API_USERNAME}
yq write --inplace kubernetes/kustomize/overlays/continuous-deploy/webserver-php-fpm-patch.yml [0].value.env[2].value ${S3_API_PASSWORD}
yq write --inplace kubernetes/kustomize/overlays/continuous-deploy/webserver-php-fpm-patch.yml [0].value.env[3].value ${S3_API_BUCKET_NAME}
yq write --inplace kubernetes/kustomize/overlays/continuous-deploy/webserver-php-fpm-patch.yml [0].value.env[4].value ${REDIS_PREFIX}
yq write --inplace kubernetes/kustomize/overlays/continuous-deploy/webserver-php-fpm-patch.yml [0].value.env[5].value ${ELASTIC_SEARCH_INDEX_PREFIX}

yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.containers[0].env[0].value ${S3_API_HOST}
yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.containers[0].env[1].value ${S3_API_USERNAME}
yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.containers[0].env[2].value ${S3_API_PASSWORD}
yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.containers[0].env[3].value ${S3_API_BUCKET_NAME}
yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.containers[0].env[4].value ${REDIS_PREFIX}
yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.containers[0].env[5].value ${ELASTIC_SEARCH_INDEX_PREFIX}

yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.initContainers[1].env[0].value ${S3_API_HOST}
yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.initContainers[1].env[1].value ${S3_API_USERNAME}
yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.initContainers[1].env[2].value ${S3_API_PASSWORD}
yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.initContainers[1].env[3].value ${S3_API_BUCKET_NAME}
yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.initContainers[1].env[4].value ${REDIS_PREFIX}
yq write --inplace kubernetes/cron/php-fpm-cron-executor.yml spec.jobTemplate.spec.template.spec.initContainers[1].env[5].value ${ELASTIC_SEARCH_INDEX_PREFIX}

# Set database IPs
yq write --inplace app/config/parameters.yml parameters.database_name ${POSTGRES_DATABASE_NAME}
yq write --inplace app/config/parameters.yml parameters.database_password ${POSTGRES_DATABASE_PASSWORD}
yq write --inplace app/config/parameters.yml parameters.database_port ${POSTGRES_DATABASE_PORT}
yq write --inplace app/config/parameters.yml parameters.database_user ${POSTGRES_DATABASE_USER}
yq write --inplace app/config/parameters.yml parameters.elasticsearch_host elasticsearch:${ELASTICSEARCH_HOST_PORT}

# set Balikobot credentials
yq write --inplace app/config/parameters.yml parameters.balikobot.username ${BALIKOBOT_USERNAME}
yq write --inplace app/config/parameters.yml parameters.balikobot.apiKey ${BALIKOBOT_API_KEY}

# Replace bucket name for S3 images URL
sed -i "s/S3_BUCKET_NAME/${S3_API_BUCKET_NAME}/g" docker/nginx/s3/nginx.conf

RUNNING_POD_EXIST=1
WEBSERVER_PHP_FPM_POD=$(kubectl get pods --namespace=${PROJECT_NAME} -l app=webserver-php-fpm --field-selector=status.phase=Running -o=jsonpath='{.items[0].metadata.name}') || RUNNING_POD_EXIST=0

if [ $FIRST_DEPLOY -eq "0" ]; then
    # Running webserver-php-fpm pod.
    # For first deploy you cant apply maintenance page
    if [ $RUNNING_POD_EXIST -eq "1" ]; then
        echo "Turning maintenance page on for running pod"
        kubectl exec ${WEBSERVER_PHP_FPM_POD} --namespace=${PROJECT_NAME} ./phing maintenance-on
    fi
fi

kubectl create namespace $PROJECT_NAME || echo "$PROJECT_NAME namespace already existing"

# deploy secret from ~/.docker/config.json if not present
kubectl create secret generic dockerhub --namespace=$PROJECT_NAME --from-file=.dockerconfigjson=/root/.docker/config.json --type=kubernetes.io/dockerconfigjson || echo "secret already present"

# Outputs all manifests by Kustomize and deploy them, manifests that has not be changed from last deploy will be skipped
# and only changed manifests will be redeployed
if [ $FIRST_DEPLOY -eq "0" ]; then
    # Output a final configuration from Kustomize for debug in jenkins
    kustomize build kubernetes/kustomize/overlays/continuous-deploy
    kustomize build kubernetes/kustomize/overlays/continuous-deploy | kubectl apply -f -
else
    # Output a final configuration from Kustomize for debug in jenkins
    kustomize build kubernetes/kustomize/overlays/first-deploy
    kustomize build kubernetes/kustomize/overlays/first-deploy | kubectl apply -f -
fi

# default value is 0 because if rollout status ends well then it would not be set because of /dev/null implementation
EXIT_CODE=0
# wait for new pod to be initialized and if it fails send result to /dev/null and save output code to a varaible.
kubectl rollout status --namespace=${PROJECT_NAME} deployment/webserver-php-fpm --watch 2> /dev/null || EXIT_CODE=$?

# if rollout status failed, it will turn maintenance on previous running instance and outputs logs of a new version that failed
if [ $EXIT_CODE -eq "0" ]; then
    echo "Rollout succesful"
else
    # For first deploy you cant apply maintenance page
    if [ $RUNNING_POD_EXIST -eq "1" ]; then
        echo "Turning maintenance page off for running pod"
        kubectl exec ${WEBSERVER_PHP_FPM_POD} --namespace=${PROJECT_NAME} ./phing maintenance-off
    fi
    CRASHED_WEBSERVER_PHP_FPM_POD=$(kubectl get pods --namespace=${PROJECT_NAME} --field-selector=status.phase!=Running -l app=webserver-php-fpm -o=jsonpath='{.items[0].metadata.name}')

    if [ $FIRST_DEPLOY -eq "1" ]; then
        echo "Echoing logs of init-application container"
        kubectl logs ${CRASHED_WEBSERVER_PHP_FPM_POD} --namespace=${PROJECT_NAME} -c init-application
    else
        echo "Echoing logs of upgrade-application container"
        kubectl logs ${CRASHED_WEBSERVER_PHP_FPM_POD} --namespace=${PROJECT_NAME} -c upgrade-application
    fi

    exit 1
fi