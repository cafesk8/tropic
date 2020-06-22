echo "Allow GoPay IP addresses access without HTTP Auth"

# Set IP allow
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
deny all;"
fi
