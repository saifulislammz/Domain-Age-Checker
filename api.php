<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

class DomainAge {
    private $WHOIS_SERVERS = array(
        "com" => array("whois.verisign-grs.com", "/Creation Date:(.*)/"),
        "net" => array("whois.verisign-grs.com", "/Creation Date:(.*)/"),
        "org" => array("whois.pir.org", "/Creation Date:(.*)/"),
        "info" => array("whois.afilias.info", "/Creation Date:(.*)/"),
        "biz" => array("whois.neulevel.biz", "/Creation Date:(.*)/"),
        "us" => array("whois.nic.us", "/Creation Date:(.*)/"),
        "uk" => array("whois.nic.uk", "/Registered on:(.*)/"),
        "ca" => array("cira.ca", "/Creation date:(.*)/"),
        "tel" => array("whois.nic.tel", "/Creation Date:(.*)/"),
        "ie" => array("whois.iedr.ie", "/Creation Date:(.*)/"),
        "it" => array("web-whois.nic.it", "/Domain Created:(.*)/"),
        "cc" => array("whois.nic.cc", "/Creation Date:(.*)/"),
        "ws" => array("whois.nic.ws", "/Domain Created:(.*)/"),
        "sc" => array("whois2.afilias-grs.net", "/Created On:(.*)/"),
        "mobi" => array("whois.dotmobiregistry.net", "/Created On:(.*)/"),
        "pro" => array("whois.registrypro.pro", "/Creation Date:(.*)/"),
        "edu" => array("whois.educause.net", "/Domain record activated:(.*)/"),
        "tv" => array("whois.nic.tv", "/Creation Date:(.*)/"),
        "travel" => array("whois.nic.travel", "/Creation Date:(.*)/"),
        "in" => array("whois.registry.in", "/Creation Date:(.*)/"),
        "me" => array("whois.nic.me", "/Creation Date:(.*)/"),
        "cn" => array("whois.cnnic.cn", "/Registration Time:(.*)/"),
        "asia" => array("whois.nic.asia", "/Domain Create Date:(.*)/"),
        "ro" => array("whois.rotld.ro", "/Registered On:(.*)/"),
        "aero" => array("whois.aero", "/Created On:(.*)/"),
        "xyz" => array("whois.nic.xyz", "/Creation Date:(.*)/"),
        "io" => array("whois.nic.io", "/Creation Date:(.*)/"),
        "pk" => array("whois.move.pk", "/Create Date:(.*)/"),
        "nu" => array("whois.nic.nu", "/Created:(.*)/"),
        "online" => array("whois.nic.online", "/Creation Date:(.*)/"),
        "app" => array("whois.nic.google", "/Creation Date:(.*)/"),
        "dev" => array("whois.nic.google", "/Creation Date:(.*)/"),
        "tech" => array("whois.nic.tech", "/Creation Date:(.*)/"),
        "ai" => array("whois.nic.ai", "/Created Date:(.*)/"),
        "co" => array("whois.nic.co", "/Creation Date:(.*)/"),
        "shop" => array("whois.nic.shop", "/Creation Date:(.*)/"),
        "site" => array("whois.nic.site", "/Creation Date:(.*)/")
    );

    public function age($domain) {
        try {
            $domain = $this->cleanDomain($domain);
            
            if (!$this->isValidDomain($domain)) {
                return false;
            }

            $domainInfo = $this->getDomainInfo($domain);
            if (!$domainInfo) {
                return false;
            }

            return $this->formatAge($domainInfo['creationDate']);

        } catch (Exception $e) {
            error_log("Domain Age Error: " . $e->getMessage());
            return false;
        }
    }

    private function cleanDomain($domain) {
        $domain = trim(strtolower($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        return $domain;
    }

    private function isValidDomain($domain) {
        return preg_match("/^([-a-z0-9]{2,100})\.([a-z\.]{2,8})$/i", $domain);
    }

    private function getDomainInfo($domain) {
        $domain_parts = explode(".", $domain);
        $tld = strtolower(array_pop($domain_parts));

        if (!isset($this->WHOIS_SERVERS[$tld])) {
            return false;
        }

        $server = $this->WHOIS_SERVERS[$tld][0];
        $pattern = $this->WHOIS_SERVERS[$tld][1];

        $whoisData = $this->queryWhois($server, $domain);
        
        if (!preg_match($pattern, $whoisData, $match)) {
            return false;
        }

        return [
            'creationDate' => strtotime(trim($match[1])),
            'tld' => $tld,
            'whoisServer' => $server
        ];
    }

    private function queryWhois($server, $domain) {
        $timeout = 20;
        $fp = @fsockopen($server, 43, $errno, $errstr, $timeout);
        
        if (!$fp) {
            throw new Exception("Socket Error $errno: $errstr");
        }

        $query = ($server === "whois.verisign-grs.com") ? "=$domain\r\n" : "$domain\r\n";
        fputs($fp, $query);

        $out = "";
        while (!feof($fp)) {
            $out .= fgets($fp);
        }
        fclose($fp);

        return $out;
    }

    private function formatAge($creationTimestamp) {
        date_default_timezone_set('UTC');
        $time = time() - $creationTimestamp;
        
        $years = floor($time / 31556926);
        $days = floor(($time % 31556926) / 86400);
        $months = floor($days / 30);
        
        $parts = [];
        
        if ($years > 0) {
            $parts[] = $years . ' ' . ($years == 1 ? 'year' : 'years');
        }
        
        if ($months > 0) {
            $parts[] = $months . ' ' . ($months == 1 ? 'month' : 'months');
        }
        
        $remainingDays = $days % 30;
        if ($remainingDays > 0) {
            $parts[] = $remainingDays . ' ' . ($remainingDays == 1 ? 'day' : 'days');
        }
        
        return implode(', ', $parts);
    }
}

// API Response Handling
try {
    if (!isset($_GET['domain'])) {
        throw new Exception("No domain provided.");
    }

    $domain = $_GET['domain'];
    $domainAge = new DomainAge();
    $age = $domainAge->age($domain);

    if ($age) {
        echo json_encode([
            "success" => true,
            "domain" => $domain,
            "age" => $age
        ]);
    } else {
        throw new Exception("Unable to determine the domain age.");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>