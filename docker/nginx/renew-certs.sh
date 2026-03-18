#!/bin/bash
# Called by certbot after renewal
cp /etc/letsencrypt/live/steelflowmrp.com/fullchain.pem /home/mark/DrawingFlow/docker/nginx/certs/tls.crt
cp /etc/letsencrypt/live/steelflowmrp.com/privkey.pem /home/mark/DrawingFlow/docker/nginx/certs/tls.key
chown mark:mark /home/mark/DrawingFlow/docker/nginx/certs/tls.crt /home/mark/DrawingFlow/docker/nginx/certs/tls.key
docker exec drawingflow-nginx nginx -s reload
