<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

try {
    echo "🔍 Checking FFmpeg availability...\n\n";
    
    // Check if FFmpeg is available
    $ffmpegOutput = [];
    $ffmpegReturnCode = 0;
    exec('ffmpeg -version 2>&1', $ffmpegOutput, $ffmpegReturnCode);
    
    if ($ffmpegReturnCode === 0) {
        echo "✅ FFmpeg is available!\n";
        echo "📋 Version information:\n";
        foreach ($ffmpegOutput as $line) {
            if (strpos($line, 'ffmpeg version') !== false) {
                echo "   " . trim($line) . "\n";
                break;
            }
        }
        
        // Test video concatenation capability
        echo "\n🧪 Testing video concatenation capability...\n";
        
        // Create a simple test video
        $testVideo1 = tempnam(sys_get_temp_dir(), 'test1_') . '.mp4';
        $testVideo2 = tempnam(sys_get_temp_dir(), 'test2_') . '.mp4';
        $testOutput = tempnam(sys_get_temp_dir(), 'test_output_') . '.mp4';
        
        // Create test videos using FFmpeg (1 second each)
        $createVideo1 = "ffmpeg -f lavfi -i testsrc=duration=1:size=320x240:rate=1 -c:v libx264 -t 1 '{$testVideo1}' -y 2>&1";
        $createVideo2 = "ffmpeg -f lavfi -i testsrc=duration=1:size=320x240:rate=1 -c:v libx264 -t 1 '{$testVideo2}' -y 2>&1";
        
        exec($createVideo1, $output1, $return1);
        exec($createVideo2, $output2, $return2);
        
        if ($return1 === 0 && $return2 === 0) {
            // Create concatenation list
            $listFile = tempnam(sys_get_temp_dir(), 'test_list_');
            $listContent = "file '{$testVideo1}'\nfile '{$testVideo2}'\n";
            file_put_contents($listFile, $listContent);
            
            // Test concatenation
            $concatCommand = "ffmpeg -f concat -safe 0 -i '{$listFile}' -c copy '{$testOutput}' 2>&1";
            $concatOutput = [];
            $concatReturnCode = 0;
            
            exec($concatCommand, $concatOutput, $concatReturnCode);
            
            // Clean up test files
            unlink($testVideo1);
            unlink($testVideo2);
            unlink($testOutput);
            unlink($listFile);
            
            if ($concatReturnCode === 0) {
                echo "✅ Video concatenation test successful!\n";
                echo "🎬 Face data video appending will work properly.\n";
            } else {
                echo "⚠️ Video concatenation test failed:\n";
                foreach ($concatOutput as $line) {
                    echo "   " . trim($line) . "\n";
                }
            }
        } else {
            echo "⚠️ Could not create test videos for concatenation test.\n";
        }
        
    } else {
        echo "❌ FFmpeg is NOT available!\n\n";
        echo "📋 Installation instructions:\n\n";
        
        echo "🔧 For Ubuntu/Debian:\n";
        echo "   sudo apt update\n";
        echo "   sudo apt install ffmpeg\n\n";
        
        echo "🔧 For CentOS/RHEL:\n";
        echo "   sudo yum install epel-release\n";
        echo "   sudo yum install ffmpeg ffmpeg-devel\n\n";
        
        echo "🔧 For Amazon Linux:\n";
        echo "   sudo yum update\n";
        echo "   sudo yum install ffmpeg\n\n";
        
        echo "🔧 For macOS (using Homebrew):\n";
        echo "   brew install ffmpeg\n\n";
        
        echo "🔧 For Windows:\n";
        echo "   1. Download from https://ffmpeg.org/download.html\n";
        echo "   2. Extract to a folder (e.g., C:\\ffmpeg)\n";
        echo "   3. Add C:\\ffmpeg\\bin to your PATH environment variable\n\n";
        
        echo "⚠️ Without FFmpeg, face data videos will be saved as separate files with timestamps instead of being concatenated.\n";
        echo "   This means each upload will create a new file: face_data_{private_key}_{timestamp}.mp4\n";
    }
    
    // Check PHP exec function
    echo "\n🔍 Checking PHP exec function...\n";
    if (function_exists('exec')) {
        echo "✅ PHP exec function is available\n";
    } else {
        echo "❌ PHP exec function is disabled\n";
        echo "   Enable it in php.ini by removing 'exec' from disable_functions\n";
    }
    
    // Check upload directory
    echo "\n🔍 Checking upload directory...\n";
    $uploadDir = 'uploads/face_data/';
    if (file_exists($uploadDir)) {
        echo "✅ Upload directory exists: {$uploadDir}\n";
        if (is_writable($uploadDir)) {
            echo "✅ Upload directory is writable\n";
        } else {
            echo "❌ Upload directory is not writable\n";
            echo "   Run: chmod 755 {$uploadDir}\n";
        }
    } else {
        echo "❌ Upload directory does not exist\n";
        echo "   Run: mkdir -p {$uploadDir} && chmod 755 {$uploadDir}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 