ARG VERSION

FROM leanderchristmann/recipe-manager:${VERSION:-0.0.0} AS php-fpm-source

FROM nginx:alpine

# Copy the public directory contents straight from the previously built leanderchristmann/recipe-manager Image
COPY --from=php-fpm-source /var/www/public /var/www/public
COPY ./docker/deployment/nginx /etc/nginx/conf.d/

WORKDIR /var/www/public

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
