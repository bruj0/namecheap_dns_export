This a PHP script that will export a Domain data from Namecheap.com to a bind Zone.

Execute as:

```
$ php namecheap_exportzone.php domain.ext user_name password
$ORIGIN domain.ext.
$TTL 3600

; NS RECORDS
demo.domain.ext.       1799    NS    ns1.domain.ext.

; A RECORDS
@                          1799    A     127.0.0.1
test.domain.com.           1799    A     127.0.0.1
```
