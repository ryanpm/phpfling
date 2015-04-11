
  <?php 

    // Include two sample files for comparison
    $a = explode("\n", file_get_contents($remote));
    $b = explode("\n", file_get_contents($local));

    // Options for generating the diff
    $options = array(
      //'ignoreWhitespace' => true,
      //'ignoreCase' => true,
    );

    // Initialize the diff class
    $diff = new ChrishPhpDiff($a, $b, $options);

    ?>
    <h2>Side by Side Diff</h2>
    <?php

    // Generate a side by side diff
    require_once dirname(__FILE__).'/../vendor/chrisboulton/php-diff/lib/Diff/Renderer/Html/SideBySide.php';
    $renderer = new Diff_Renderer_Html_SideBySide;
    $result = $diff->Render($renderer);

    if( $result != '' ){
      echo $result;
    }else{
      echo "No Difference";
    }

  ?>


  <!-- <h2>Inline Diff</h2> -->
  <?php 
   // Generate an inline diff
    // require_once dirname(__FILE__).'/../vendor/chrisboulton/php-diff/lib/Diff/Renderer/Html/Inline.php';
    // $renderer = new Diff_Renderer_Html_Inline;
    // echo $diff->render($renderer);

   ?>


  <!-- <h2>Unified Diff</h2>
    <pre>
   -->  <?php
    // Generate a unified diff
    // require_once dirname(__FILE__).'/../vendor/chrisboulton/php-diff/lib/Diff/Renderer/Text/Unified.php';
    // $renderer = new Diff_Renderer_Text_Unified;
    // echo htmlspecialchars($diff->render($renderer));
    ?>
    <!-- </pre> -->

<!--     <h2>Context Diff</h2>
    <pre>
 -->    <?php
    // Generate a context diff
    // require_once dirname(__FILE__).'/../vendor/chrisboulton/php-diff/lib/Diff/Renderer/Text/Context.php';
    // $renderer = new Diff_Renderer_Text_Context;
    // echo htmlspecialchars($diff->render($renderer));
    ?>
    <!-- </pre> -->