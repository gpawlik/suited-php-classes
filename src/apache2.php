<?php

namespace diversen;

use diversen\strings\version as strings_version;

/**
 * file contains class for doing insall, removal of apache2 virtual hosts
 * @package apache2
 */

/**
 * class contains class for doing insall, removal of apache2 virtual hosts
 * @package apache2
 */
class apache2 {

    /**
     * sets a flag indicating to use apache2 with SSL
     * @param array $options
     */
    public static function setUseSSL($options) {
        conf::setMainIni('a2_use_ssl', true);
    }

    /**
     * create apache log files
     */
    public static function createLogs() {
        touch(conf::pathBase() . '/logs/access.log');
        touch(conf::pathBase() . '/logs/error.log');
    }

    /**
     * create apache2 configuration string
     * Note: we don't use the conf::pathHtdocs() path. 
     * @param   string  $server_name the host to enable
     * @return  string  $config an apache2 configuration string.
     */
    public static function getA2Conf($SERVER_NAME) {
        $current_dir = getcwd();
        $DOCUMENT_ROOT = $current_dir . '/htdocs';
        $APACHE_LOG_ROOT = $current_dir . '/logs';

        if (isset(conf::$vars['a2_use_ssl'])) {
            $apache_str = apache2::getConf($SERVER_NAME, $DOCUMENT_ROOT, $APACHE_LOG_ROOT);
        } else {
            $apache_str = apache2::getConfSSL($SERVER_NAME, $DOCUMENT_ROOT, $APACHE_LOG_ROOT);
        }
        return $apache_str;
    }

    /**
     * function for enabling a aapche2 site
     * the script does the following:
     *
     * - create access.log and error.log in ./logs
     * - create virtual configuration and put it in sites-available
     * - enable new site
     * - create new /etc/hosts file
     *
     * @param array $options only options is $options[sitename] 
     */
    public static function enableSite($options) {
        $hostname = trim($options['hostname']);

        cos_needs_root();
        self::createLogs();

        // create apache2 conf and enable site
        $apache2_conf = self::getA2Conf($hostname);
        $tmp_file = conf::pathBase() . "/tmp/$hostname";
        file_put_contents($tmp_file, $apache2_conf);
        $apache2_conf_file = "/etc/apache2/sites-available/$hostname";

        // some changes in apache 2.4.x from apache 2.2.x
        // se http://httpd.apache.org/docs/current/upgrading.html

        $version = apache2::getVersion();
        $version = strings_version::getSemanticAry($version);

        if ($version['minor'] >= 4) {
            $apache2_conf_file.= ".conf";
        }

        cos_exec("cp -f tmp/$hostname $apache2_conf_file");
        cos_exec("a2ensite $hostname");

        // create new hosts file and reload server
        // not very exact match

        $hosts_file_str = file_get_contents("/etc/hosts");
        $host_str = "127.0.0.1\t$hostname\n";
        if (!strstr($hosts_file_str, $host_str)) {
            $new_hosts_file_str = $host_str . $hosts_file_str;
            file_put_contents("tmp/hosts", $new_hosts_file_str);
            cos_exec("cp -f tmp/hosts /etc/hosts");
        }
        cos_exec("/etc/init.d/apache2 reload");
    }

    /**
     * function for disabling an apache2 site
     * @param array $options only options is $options[sitename] 
     */
    public static function disableSite($options) {

        cos_needs_root();
        $hostname = trim($options['hostname']);

        $apache2_conf_file = "/etc/apache2/sites-available/$hostname";
        $ret = cos_exec("a2dissite $hostname");
        if ($ret) {
            return false;
        }

        $version = apache2::getVersion();
        $version = strings_version::getSemanticAry($version);

        if ($version['minor'] >= 4) {
            $apache2_conf_file.= ".conf";
        }

        $ret = cos_exec("rm -f $apache2_conf_file");

        // create new hosts file and reload server
        $host_file_str = '';
        $hosts_file_str = file("/etc/hosts");

        $host_search = "127.0.0.1\t$hostname\n";
        $str = "";
        foreach ($hosts_file_str as $key => $val) {
            if (strstr($val, $host_search)) {
                continue;
            } else {
                $host_file_str.=$val;
            }
        }
        file_put_contents("tmp/hosts", $host_file_str);
        cos_exec("cp -f tmp/hosts /etc/hosts");
        cos_exec("/etc/init.d/apache2 reload");
    }

    /**
     * 
     * @return int $ret 0 if exists 1 if not exits
     */
    public static function isInstalled() {
        exec("which apache2", $ary, $ret);
        if ($ret == 0) {
            return true;
        }
        return false;
    }

    /**
     * gets apache2 version running
     * @return string
     */
    public static function getVersion() {
        exec('apache2 -v', $ary, $ret);
        $line = $ary[0];
        $ary = explode(':', $line);
        $ary[1];
        $ary = explode('/', $ary[1]);
        preg_match("/\d+(\.\d+)+/", $ary[1], $matches);
        return $matches[0];
    }

    /**
     * gets a configuration file
     * it knows about apache2 changes in 2_4_x
     * where conf files needs to end on .conf
     * @param string $SERVER_NAME
     * @param string $DOCUMENT_ROOT
     * @param string $APACHE_LOG_ROOT
     * @return string $conf a apache2 conf file
     */
    public static function getConf($SERVER_NAME, $DOCUMENT_ROOT, $APACHE_LOG_ROOT) {
        $version = self::getVersion();
        $version = strings_version::getSemanticAry($version);

        if ($version['minor'] >= 4) {
            $VERSION_2_4_x = <<<EOF
        Options FollowSymLinks
        AllowOverride All
        Require all granted
EOF;
        } else {
            $VERSION_2_4_x = '';
        }

        return $apache_str = <<<EOD
<VirtualHost *:80>
    ServerAdmin webmaster@example.com
    ServerName  {$SERVER_NAME}

    # Indexes + Directory Root.
    DirectoryIndex index.php
    DocumentRoot {$DOCUMENT_ROOT}
  
    <Directory {$DOCUMENT_ROOT}>
        {$VERSION_2_4_x}
        RewriteEngine on
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php?q=$1 [L,QSA]
    </Directory>

<Files *.inc>
deny from all
</Files>

<Files info.php>
deny from all
allow from 127.0.0.1
</Files>

<Files *.sql>
deny from all
</Files>

<Files *.ini>
deny from all
</Files>
   
    ErrorLog  {$APACHE_LOG_ROOT}/error.log
    CustomLog {$APACHE_LOG_ROOT}/access.log combined
</VirtualHost>
EOD;
    }

    /**
     * difference in conf files in version 2.4.x
     * @return string
     */
    public static function get2_4_conf() {
        $str = <<<EOF
    Options FollowSymLinks
    AllowOverride All
    Require all granted
EOF;
        return $str;
    }

    /**
     * get apache2 with ssl configuration
     * @param string $SERVER_NAME
     * @param string $DOCUMENT_ROOT
     * @param string $APACHE_LOG_ROOT
     * @return string $conf apache2 ssl configuration
     */
    public static function getConfSSL($SERVER_NAME, $DOCUMENT_ROOT, $APACHE_LOG_ROOT) {
        $version = apache2::getVersion();
        $version = strings_version::getSemanticAry($version);

        if ($version['minor'] >= 4) {
            $VERSION_2_4_x = <<<EOF
        Options FollowSymLinks
        AllowOverride All
        Require all granted
EOF;
        } else {
            $VERSION_2_4_x = '';
        }

        return $apache_str = <<<EOD
<VirtualHost *:80>
    ServerAdmin webmaster@example.com
    ServerName  {$SERVER_NAME}

    # Indexes + Directory Root.
    DirectoryIndex index.php
    DocumentRoot {$DOCUMENT_ROOT}
  
    <Directory {$DOCUMENT_ROOT}>
        {$VERSION_2_4_x}
        RewriteEngine on
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php?q=$1 [L,QSA]
    </Directory>

<Files *.inc>
deny from all
</Files>

<Files info.php>
deny from all
allow from 127.0.0.1
</Files>

<Files *.sql>
deny from all
</Files>

<Files *.ini>
deny from all
</Files>
   
    ErrorLog  {$APACHE_LOG_ROOT}/error.log
    CustomLog {$APACHE_LOG_ROOT}/access.log combined
</VirtualHost>

<IfModule mod_ssl.c>
<VirtualHost _default_:443>
	
    ServerAdmin webmaster@example.com
    ServerName  {$SERVER_NAME}

    # Indexes + Directory Root.
    DirectoryIndex index.php
    DocumentRoot {$DOCUMENT_ROOT}
  
    <Directory {$DOCUMENT_ROOT}>
        {$VERSION_2_4_x}
        RewriteEngine on
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php?q=$1 [L,QSA]
    </Directory>

<Files *.inc>
deny from all
</Files>

<Files info.php>
deny from all
allow from 127.0.0.1
</Files>

<Files *.sql>
deny from all
</Files>

<Files *.ini>
deny from all
</Files>
	
        ErrorLog  {$APACHE_LOG_ROOT}/error.log
        CustomLog {$APACHE_LOG_ROOT}/access.log combined

	# Possible values include: debug, info, notice, warn, error, crit,
	# alert, emerg.
	LogLevel warn


	

	#   SSL Engine Switch:
	#   Enable/Disable SSL for this virtual host.
	SSLEngine on

	#   A self-signed (snakeoil) certificate can be created by installing
	#   the ssl-cert package. See
	#   /usr/share/doc/apache2.2-common/README.Debian.gz for more info.
	#   If both key and certificate are stored in the same file, only the
	#   SSLCertificateFile directive is needed.
	SSLCertificateFile    /etc/ssl/certs/ssl-cert-snakeoil.pem
	SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

	#   Server Certificate Chain:
	#   Point SSLCertificateChainFile at a file containing the
	#   concatenation of PEM encoded CA certificates which form the
	#   certificate chain for the server certificate. Alternatively
	#   the referenced file can be the same as SSLCertificateFile
	#   when the CA certificates are directly appended to the server
	#   certificate for convinience.
	#SSLCertificateChainFile /etc/apache2/ssl.crt/server-ca.crt

	#   Certificate Authority (CA):
	#   Set the CA certificate verification path where to find CA
	#   certificates for client authentication or alternatively one
	#   huge file containing all of them (file must be PEM encoded)
	#   Note: Inside SSLCACertificatePath you need hash symlinks
	#         to point to the certificate files. Use the provided
	#         Makefile to update the hash symlinks after changes.
	#SSLCACertificatePath /etc/ssl/certs/
	#SSLCACertificateFile /etc/apache2/ssl.crt/ca-bundle.crt

	#   Certificate Revocation Lists (CRL):
	#   Set the CA revocation path where to find CA CRLs for client
	#   authentication or alternatively one huge file containing all
	#   of them (file must be PEM encoded)
	#   Note: Inside SSLCARevocationPath you need hash symlinks
	#         to point to the certificate files. Use the provided
	#         Makefile to update the hash symlinks after changes.
	#SSLCARevocationPath /etc/apache2/ssl.crl/
	#SSLCARevocationFile /etc/apache2/ssl.crl/ca-bundle.crl

	#   Client Authentication (Type):
	#   Client certificate verification type and depth.  Types are
	#   none, optional, require and optional_no_ca.  Depth is a
	#   number which specifies how deeply to verify the certificate
	#   issuer chain before deciding the certificate is not valid.
	#SSLVerifyClient require
	#SSLVerifyDepth  10

	#   Access Control:
	#   With SSLRequire you can do per-directory access control based
	#   on arbitrary complex boolean expressions containing server
	#   variable checks and other lookup directives.  The syntax is a
	#   mixture between C and Perl.  See the mod_ssl documentation
	#   for more details.
	#<Location />
	#SSLRequire (    %{SSL_CIPHER} !~ m/^(EXP|NULL)/ \
	#            and %{SSL_CLIENT_S_DN_O} eq "Snake Oil, Ltd." \
	#            and %{SSL_CLIENT_S_DN_OU} in {"Staff", "CA", "Dev"} \
	#            and %{TIME_WDAY} >= 1 and %{TIME_WDAY} <= 5 \
	#            and %{TIME_HOUR} >= 8 and %{TIME_HOUR} <= 20       ) \
	#           or %{REMOTE_ADDR} =~ m/^192\.76\.162\.[0-9]+$/
	#</Location>

	#   SSL Engine Options:
	#   Set various options for the SSL engine.
	#   o FakeBasicAuth:
	#     Translate the client X.509 into a Basic Authorisation.  This means that
	#     the standard Auth/DBMAuth methods can be used for access control.  The
	#     user name is the `one line' version of the client's X.509 certificate.
	#     Note that no password is obtained from the user. Every entry in the user
	#     file needs this password: `xxj31ZMTZzkVA'.
	#   o ExportCertData:
	#     This exports two additional environment variables: SSL_CLIENT_CERT and
	#     SSL_SERVER_CERT. These contain the PEM-encoded certificates of the
	#     server (always existing) and the client (only existing when client
	#     authentication is used). This can be used to import the certificates
	#     into CGI scripts.
	#   o StdEnvVars:
	#     This exports the standard SSL/TLS related `SSL_*' environment variables.
	#     Per default this exportation is switched off for performance reasons,
	#     because the extraction step is an expensive operation and is usually
	#     useless for serving static content. So one usually enables the
	#     exportation for CGI and SSI requests only.
	#   o StrictRequire:
	#     This denies access when "SSLRequireSSL" or "SSLRequire" applied even
	#     under a "Satisfy any" situation, i.e. when it applies access is denied
	#     and no other module can change it.
	#   o OptRenegotiate:
	#     This enables optimized SSL connection renegotiation handling when SSL
	#     directives are used in per-directory context.
	#SSLOptions +FakeBasicAuth +ExportCertData +StrictRequire
	<FilesMatch "\.(cgi|shtml|phtml|php)$">
		SSLOptions +StdEnvVars
	</FilesMatch>
	<Directory /usr/lib/cgi-bin>
		SSLOptions +StdEnvVars
	</Directory>

	#   SSL Protocol Adjustments:
	#   The safe and default but still SSL/TLS standard compliant shutdown
	#   approach is that mod_ssl sends the close notify alert but doesn't wait for
	#   the close notify alert from client. When you need a different shutdown
	#   approach you can use one of the following variables:
	#   o ssl-unclean-shutdown:
	#     This forces an unclean shutdown when the connection is closed, i.e. no
	#     SSL close notify alert is send or allowed to received.  This violates
	#     the SSL/TLS standard but is needed for some brain-dead browsers. Use
	#     this when you receive I/O errors because of the standard approach where
	#     mod_ssl sends the close notify alert.
	#   o ssl-accurate-shutdown:
	#     This forces an accurate shutdown when the connection is closed, i.e. a
	#     SSL close notify alert is send and mod_ssl waits for the close notify
	#     alert of the client. This is 100% SSL/TLS standard compliant, but in
	#     practice often causes hanging connections with brain-dead browsers. Use
	#     this only for browsers where you know that their SSL implementation
	#     works correctly.
	#   Notice: Most problems of broken clients are also related to the HTTP
	#   keep-alive facility, so you usually additionally want to disable
	#   keep-alive for those clients, too. Use variable "nokeepalive" for this.
	#   Similarly, one has to force some clients to use HTTP/1.0 to workaround
	#   their broken HTTP/1.1 implementation. Use variables "downgrade-1.0" and
	#   "force-response-1.0" for this.
	BrowserMatch "MSIE [2-6]" \
		nokeepalive ssl-unclean-shutdown \
		downgrade-1.0 force-response-1.0
	# MSIE 7 and newer should be able to use keepalive
	BrowserMatch "MSIE [17-9]" ssl-unclean-shutdown

</VirtualHost>
</IfModule>
EOD;
    }
}
