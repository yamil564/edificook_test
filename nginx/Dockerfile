FROM nginx:latest
COPY default.conf /etc/nginx/conf.d/default.conf

RUN apt-get update && apt-get install -y apt-utils  wget curl ssmtp xz-utils  git
RUN apt-get install -y --no-install-recommends zlib1g fontconfig libfreetype6 libx11-6 libxext6 libxrender1 && rm -rf /var/lib/apt/lists/*
## INSTALL WKHTMLTOPDF
RUN wget https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/0.12.3/wkhtmltox-0.12.3_linux-generic-amd64.tar.xz && tar xf wkhtmltox-0.12.3_linux-generic-amd64.tar.xz && cp wkhtmltox/bin/wkhtmltopdf /usr/local/bin/
