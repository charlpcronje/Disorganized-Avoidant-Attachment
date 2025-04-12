<?php
// Example file showing how to use talk tags
?>
<div class="page-header">
    <div class="container">
        <h1>Using Talk Tags</h1>
        <p class="description">This page demonstrates how to use talk tags for text-to-speech functionality.</p>
    </div>
</div>
<div class="content-container page-content">
    <h2>Basic Usage</h2>
    
    <!-- Example of a talk tag with default voice -->
    <talk>
        <p>This text will be read using the default voice (echo). You can wrap any content in talk tags to make it readable by the text-to-speech system.</p>
        
        <p>Multiple paragraphs can be included in a single talk tag. The system will read all content within the tags as a single unit.</p>
    </talk>
    
    <h2>Specifying a Voice</h2>
    
    <!-- Example of a talk tag with a specific voice -->
    <talk voice="alloy">
        <p>This text will be read using the alloy voice. You can specify different voices for different sections of content.</p>
        
        <ul>
            <li>Lists work too!</li>
            <li>Each item will be read in sequence.</li>
            <li>HTML formatting is preserved during playback.</li>
        </ul>
    </talk>
    
    <h2>Talk Tags with Links</h2>
    
    <!-- Example of a talk tag with links -->
    <talk voice="echo">
        <p>Talk tags can contain <a href="#">links</a> and other HTML elements. The text-to-speech system will preserve the HTML structure while reading the text.</p>
        
        <p>After reading a section with links, the system will announce how many links were in the text that was just read to you.</p>
    </talk>
    
    <h2>Using CSS Classes</h2>
    
    <!-- Example of using a class instead of a tag -->
    <div class="talk" voice="alloy">
        <p>If you prefer, you can also use the "talk" class instead of the talk tag. This works exactly the same way.</p>
        
        <p>This can be useful when you need to apply the text-to-speech functionality to existing HTML elements.</p>
    </div>
    
    <h2>Implementation Notes</h2>
    
    <p>The talk tags are processed by the TalkAPI JavaScript library, which:</p>
    
    <ol>
        <li>Finds all talk tags and elements with the "talk" class</li>
        <li>Replaces them with a section that includes a play button</li>
        <li>Preserves the original HTML structure and content</li>
        <li>Uses the specified voice or falls back to the default</li>
    </ol>
    
    <p>This approach gives you full control over which content is read aloud, while maintaining a clean and semantic HTML structure.</p>
</div>
