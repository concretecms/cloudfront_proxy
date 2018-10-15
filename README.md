# Cloudfront Proxy Addon
This addon sets up Cloudfront IPs as trusted proxy IPs. In doing so it resolves the session invalidation you'll see if 
using Cloudfront


### Installation

**Composer Based concrete5**

If you're using composer based c5, all you have to do is require the package and install it.
```bash
$ composer require concrete5/cloudfront_proxy
$  ./vendor/bin/concrete5 c5:package-install cloudfront_proxy
```

**Traditional concrete5**

Download a zip of this repository and extract it in your website's package directory.

