/**
 * Fluid Typography Calculator JavaScript
 * 
 * Handles the calculation of fluid typography values and generates the CSS code.
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Handle custom scale display for min viewport
        $('#min-type-scale').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#min-custom-scale').show();
            } else {
                $('#min-custom-scale').hide();
            }
        });
        
        // Handle custom scale display for max viewport
        $('#max-type-scale').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#max-custom-scale').show();
            } else {
                $('#max-custom-scale').hide();
            }
        });
        
        // Copy to clipboard functionality
        $('#copy-css').on('click', function() {
            const cssCode = $('#css-output').text();
            
            // Create a temporary textarea element
            const textarea = document.createElement('textarea');
            textarea.value = cssCode;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            
            // Select and copy the text
            textarea.select();
            document.execCommand('copy');
            
            // Remove the temporary element
            document.body.removeChild(textarea);
            
            // Show copied confirmation
            const originalText = $('#copy-css').text();
            $('#copy-css').text('Copied!');
            setTimeout(function() {
                $('#copy-css').text(originalText);
            }, 2000);
        });
        
        // Calculate button click handler
        $('#calculate-fluid-typography').on('click', function() {
            calculateFluidTypography();
        });
        
        // Initial calculation on page load
        calculateFluidTypography();
        
        /**
         * Calculate and display fluid typography
         */
        function calculateFluidTypography() {
            // Get input values
            const minViewportWidth = parseFloat($('#min-viewport-width').val());
            const maxViewportWidth = parseFloat($('#max-viewport-width').val());
            const minFontSize = parseFloat($('#min-font-size').val());
            const maxFontSize = parseFloat($('#max-font-size').val());
            
            // Get scale values
            let minScale = $('#min-type-scale').val();
            minScale = minScale === 'custom' ? parseFloat($('#min-custom-scale').val()) : parseFloat(minScale);
            
            let maxScale = $('#max-type-scale').val();
            maxScale = maxScale === 'custom' ? parseFloat($('#max-custom-scale').val()) : parseFloat(maxScale);
            
            // Calculate values
            const steps = 5; // Base size + 4 steps up
            let cssOutput = '';
            let previewStyles = '';
            
            // Generate CSS variables
            cssOutput += `:root {\n`;
            cssOutput += `  /* Fluid typography variables */\n`;
            cssOutput += `  --min-viewport-width: ${minViewportWidth}px;\n`;
            cssOutput += `  --max-viewport-width: ${maxViewportWidth}px;\n`;
            cssOutput += `  --min-font-size: ${minFontSize}px;\n`;
            cssOutput += `  --max-font-size: ${maxFontSize}px;\n`;
            cssOutput += `  --min-scale-ratio: ${minScale};\n`;
            cssOutput += `  --max-scale-ratio: ${maxScale};\n\n`;
            
            // Calculate sizes for each step
            for (let i = 0; i < steps; i++) {
                const minStepSize = minFontSize * Math.pow(minScale, i);
                const maxStepSize = maxFontSize * Math.pow(maxScale, i);
                
                // Calculate fluid formula
                const slope = (maxStepSize - minStepSize) / (maxViewportWidth - minViewportWidth);
                const yAxisIntersection = -minViewportWidth * slope + minStepSize;
                const fluidFormula = `clamp(${minStepSize.toFixed(2)}px, ${yAxisIntersection.toFixed(2)}px + ${(slope * 100).toFixed(4)}vw, ${maxStepSize.toFixed(2)}px)`;
                
                // Add to CSS output
                const varName = i === 0 ? 'font-base' : `font-scale-${i}`;
                cssOutput += `  --${varName}: ${fluidFormula};\n`;
                
                // Add preview styles
                previewStyles += `.fluid-text-step-${i} {
  font-size: var(--${varName});
}\n`;
            }
            
            cssOutput += `}\n\n`;
            cssOutput += previewStyles;
            
            // Display the results
            $('#css-output').text(cssOutput);
            
            // Show the results container
            $('#results-container').show();
            
            // Apply styles to preview
            updatePreviewStyles(cssOutput);
        }
        
        /**
         * Update the preview with the generated styles
         */
        function updatePreviewStyles(cssCode) {
            // Remove old style element if it exists
            $('#fluid-typography-preview-styles').remove();
            
            // Create and append new style element
            $('head').append('<style id="fluid-typography-preview-styles">' + cssCode + '</style>');
        }
    });
})(jQuery);
