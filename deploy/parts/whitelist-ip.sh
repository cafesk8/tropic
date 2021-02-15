echo -n "Whitelist IP addresses "

assertVariable "RUNNING_PRODUCTION"
assertVariable "CONFIGURATION_TARGET_PATH"
assertVariable "DOMAINS"

# Do not run this script if there is no domains with HTTP AUTH
if [ ${RUNNING_PRODUCTION} -eq "1" ] && [ ${#FORCE_HTTP_AUTH_IN_PRODUCTION[@]} -ne "1" ]; then
  echo -e "[${YELLOW}SKIP${NO_COLOR}]"
  return
fi

DOMAIN_ITERATOR=0

# Configure IP addresses for Domain with HTTP auth
for DOMAIN in ${DOMAINS[@]}; do
    INGRESS_FILENAME="ingress-${DOMAIN_ITERATOR}.yaml"

    if [ ${RUNNING_PRODUCTION} -eq "0" ] || containsElement ${DOMAIN} ${FORCE_HTTP_AUTH_IN_PRODUCTION[@]}; then
        yq write --inplace "${CONFIGURATION_TARGET_PATH}/ingress/${INGRESS_FILENAME}" metadata.annotations."\"nginx.ingress.kubernetes.io/configuration-snippet\"" "satisfy any;
allow 52.28.96.25;
allow 54.93.48.200;
allow 52.18.77.79;
allow 52.28.11.107;
allow 52.28.63.7;
allow 52.28.96.25;
allow 54.93.39.13;
allow 54.93.48.200;

allow 93.185.110.99/32;
allow 93.185.110.100/32;
allow 93.185.110.101/32;
allow 185.198.191.147/32;
allow 204.145.66.226/32;
allow 77.81.119.26/32;
allow 86.105.155.150/32;
allow 185.115.0.0/24;
allow 77.247.124.1/32;
deny all;"
    fi

    DOMAIN_ITERATOR=$(expr $DOMAIN_ITERATOR + 1)
done

echo -e "[${GREEN}OK${NO_COLOR}]"
