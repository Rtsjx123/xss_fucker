include_once 'htmlFilter.php';

header("Content-Type: text/html;charset=utf8");

$test = <<<'EOF'
<script>alert(/xss1/);</script>
<img src=x onerror=alert(/xss2/) />
<input type="text" style="width: 100%; height: 100%;" onmouserover='alert(/xss3/)' value="click me" />
EOF;

$hf = new htmlFilter();

echo $hf->safeHTML($test);
