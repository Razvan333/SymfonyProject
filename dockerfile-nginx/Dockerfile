FROM nginx:latest

COPY nginx.conf /etc/nginx/conf.d/default.conf
RUN echo "upstream php-upstream { server php-fpm:9000; }" > /etc/nginx/conf.d/upstream.conf
# Expose port 80
EXPOSE 80
EXPOSE 443
