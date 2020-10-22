echo "Allow GoPay IP addresses access without HTTP Auth"

# Set IP allow (the first batch are GoPay addresses and the second are CDN addresses)
if [ ${RUNNING_PRODUCTION} -eq "0" ]; then
    yq write --inplace "${CONFIGURATION_TARGET_PATH}/ingress.yaml" metadata.annotations."\"nginx.ingress.kubernetes.io/configuration-snippet\"" "satisfy any;
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
