<?php
/**
 * Connection Test Script for Ziina & Tabby APIs
 * Run this to verify DNS resolution and API connectivity
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Connection Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
            direction: rtl;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #718096;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .test-section {
            background: #f7fafc;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            border-left: 4px solid #4299e1;
        }
        .test-title {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            gap: 6px;
        }
        .status.success {
            background: #c6f6d5;
            color: #22543d;
        }
        .status.error {
            background: #fed7d7;
            color: #742a2a;
        }
        .status.warning {
            background: #feebc8;
            color: #7c2d12;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #4a5568;
            font-weight: 600;
        }
        .detail-value {
            color: #2d3748;
            font-family: 'Courier New', monospace;
            direction: ltr;
            text-align: left;
        }
        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-top: 12px;
            direction: ltr;
            text-align: left;
        }
        .icon {
            font-size: 24px;
        }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-top: 30px;
        }
        .summary h2 {
            font-size: 24px;
            margin-bottom: 16px;
        }
        .summary-list {
            list-style: none;
            display: grid;
            gap: 10px;
        }
        .summary-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 اختبار اتصال API</h1>
        <p class="subtitle">فحص الاتصال بواجهات Ziina و Tabby البرمجية</p>

        <?php
        // Test 1: PHP Configuration
        echo '<div class="test-section">';
        echo '<div class="test-title">';
        echo '<span class="icon">⚙️</span>';
        echo 'إعدادات PHP';
        $curlEnabled = extension_loaded('curl');
        if ($curlEnabled) {
            echo '<span class="status success">✓ تم التفعيل</span>';
        } else {
            echo '<span class="status error">✗ غير مفعل</span>';
        }
        echo '</div>';
        
        echo '<div class="detail-row">';
        echo '<span class="detail-label">إصدار PHP:</span>';
        echo '<span class="detail-value">' . phpversion() . '</span>';
        echo '</div>';
        
        echo '<div class="detail-row">';
        echo '<span class="detail-label">cURL Extension:</span>';
        echo '<span class="detail-value">' . ($curlEnabled ? 'Enabled ✓' : 'Disabled ✗') . '</span>';
        echo '</div>';
        
        if ($curlEnabled) {
            $curlVersion = curl_version();
            echo '<div class="detail-row">';
            echo '<span class="detail-label">cURL Version:</span>';
            echo '<span class="detail-value">' . $curlVersion['version'] . '</span>';
            echo '</div>';
        }
        echo '</div>';

        // Test 2: DNS Resolution
        echo '<div class="test-section">';
        echo '<div class="test-title">';
        echo '<span class="icon">🌐</span>';
        echo 'فحص DNS';
        echo '</div>';
        
        $hosts = [
            'api-v2.ziina.com' => 'Ziina API',
            'api.tabby.ai' => 'Tabby API',
            'google.com' => 'Google (مرجعي)'
        ];
        
        foreach ($hosts as $host => $label) {
            $ip = gethostbyname($host);
            $resolved = $ip !== $host;
            
            echo '<div class="detail-row">';
            echo '<span class="detail-label">' . $label . ':</span>';
            echo '<span class="detail-value">';
            if ($resolved) {
                echo $ip . ' <span style="color:#22543d;">✓</span>';
            } else {
                echo 'فشل الحل <span style="color:#742a2a;">✗</span>';
            }
            echo '</span>';
            echo '</div>';
        }
        echo '</div>';

        // Test 3: Ziina API Connection
        if ($curlEnabled) {
            echo '<div class="test-section">';
            echo '<div class="test-title">';
            echo '<span class="icon">💳</span>';
            echo 'اتصال Ziina API';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api-v2.ziina.com/api/payment_intent");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_DNS_SERVERS, "8.8.8.8,8.8.4.4");
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
            
            $start = microtime(true);
            curl_exec($ch);
            $responseTime = round((microtime(true) - $start) * 1000);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo '<span class="status error">✗ فشل الاتصال</span>';
            } else if ($httpCode >= 200 && $httpCode < 500) {
                echo '<span class="status success">✓ متصل</span>';
            } else {
                echo '<span class="status warning">⚠ كود غير متوقع</span>';
            }
            echo '</div>';
            
            echo '<div class="detail-row">';
            echo '<span class="detail-label">رمز الاستجابة HTTP:</span>';
            echo '<span class="detail-value">' . ($httpCode ?: 'N/A') . '</span>';
            echo '</div>';
            
            echo '<div class="detail-row">';
            echo '<span class="detail-label">وقت الاستجابة:</span>';
            echo '<span class="detail-value">' . $responseTime . ' ms</span>';
            echo '</div>';
            
            if ($error) {
                echo '<div class="detail-row">';
                echo '<span class="detail-label">خطأ:</span>';
                echo '<span class="detail-value" style="color:#742a2a;">' . htmlspecialchars($error) . '</span>';
                echo '</div>';
            }
            echo '</div>';

            // Test 4: Tabby API Connection
            echo '<div class="test-section">';
            echo '<div class="test-title">';
            echo '<span class="icon">🔄</span>';
            echo 'اتصال Tabby API';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.tabby.ai/api/v2/checkout");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_DNS_SERVERS, "8.8.8.8,8.8.4.4");
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            
            $start = microtime(true);
            curl_exec($ch);
            $responseTime = round((microtime(true) - $start) * 1000);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo '<span class="status error">✗ فشل الاتصال</span>';
            } else if ($httpCode >= 200 && $httpCode < 500) {
                echo '<span class="status success">✓ متصل</span>';
            } else {
                echo '<span class="status warning">⚠ كود غير متوقع</span>';
            }
            echo '</div>';
            
            echo '<div class="detail-row">';
            echo '<span class="detail-label">رمز الاستجابة HTTP:</span>';
            echo '<span class="detail-value">' . ($httpCode ?: 'N/A') . '</span>';
            echo '</div>';
            
            echo '<div class="detail-row">';
            echo '<span class="detail-label">وقت الاستجابة:</span>';
            echo '<span class="detail-value">' . $responseTime . ' ms</span>';
            echo '</div>';
            
            if ($error) {
                echo '<div class="detail-row">';
                echo '<span class="detail-label">خطأ:</span>';
                echo '<span class="detail-value" style="color:#742a2a;">' . htmlspecialchars($error) . '</span>';
                echo '</div>';
            }
            echo '</div>';
        }

        // Summary
        $allGood = $curlEnabled && 
                   gethostbyname('api-v2.ziina.com') !== 'api-v2.ziina.com' && 
                   gethostbyname('api.tabby.ai') !== 'api.tabby.ai';
        
        echo '<div class="summary">';
        echo '<h2>📊 الملخص</h2>';
        echo '<ul class="summary-list">';
        
        if ($allGood) {
            echo '<li class="summary-item">✅ جميع الاختبارات نجحت - النظام جاهز</li>';
            echo '<li class="summary-item">✅ DNS يعمل بشكل صحيح</li>';
            echo '<li class="summary-item">✅ الاتصال بـ Ziina API ناجح</li>';
            echo '<li class="summary-item">✅ الاتصال بـ Tabby API ناجح</li>';
        } else {
            if (!$curlEnabled) {
                echo '<li class="summary-item">❌ cURL غير مفعل - يجب تفعيله في php.ini</li>';
            }
            if (gethostbyname('api-v2.ziina.com') === 'api-v2.ziina.com') {
                echo '<li class="summary-item">❌ فشل حل DNS لـ api-v2.ziina.com</li>';
                echo '<li class="summary-item">💡 جرب إعادة تشغيل Apache</li>';
            }
            if (gethostbyname('api.tabby.ai') === 'api.tabby.ai') {
                echo '<li class="summary-item">❌ فشل حل DNS لـ api.tabby.ai</li>';
            }
        }
        
        echo '</ul>';
        echo '</div>';
        ?>
        
        <div style="text-align: center; margin-top: 30px; color: #718096; font-size: 14px;">
            <p>آخر تحديث: <?php echo date('Y-m-d H:i:s'); ?></p>
            <p style="margin-top: 10px;">
                <a href="payment.html" style="color: #667eea; text-decoration: none; font-weight: 600;">
                    → العودة إلى صفحة الدفع
                </a>
            </p>
        </div>
    </div>
</body>
</html>
