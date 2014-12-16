var Scribble = window.Scribble || {};
(Scribble.Map = function() {
    return {
        embed: function() {
            if (typeof this.configUrl !== 'string' || typeof tb_show !== 'function') {
                return;
            }
            var url = this.configUrl + ((this.configUrl.match(/\?/)) ? "&" : "?") + "TB_iframe=true&width=550";
            tb_show('Scribble Map Embed', url, false);
        }
    };
}());

/*
 Generator specific script
 */
(Scribble.Map.Generator = function() {

    var buildTag = function() {

        var $generator = jQuery('#scribble-insert'),
                tag = '[scribblemaps',
                width = "",
                height = "";

        if ($generator.length === 0) {
            return "";
        }

        // Get the basic attributes.
        $generator.find('input[type=text],input[type=hidden],input:checked,select').each(function() {

            var $this = jQuery(this);
            switch (this.name) {
                case "width":
                    width = this.value;
                    break;

                case "height":
                    height = this.value;
                    break;

                case "unit":
                    tag += ' width="' + width + ((this.value === 'percentage') ? '%' : '') + '"';
                    tag += ' height="' + height + ((this.value === 'percentage') ? '%' : '') + '"';
                    break;

                default:
                    if (this.type === "checkbox") {
                        tag += ' ' + $this.attr('name') + '="1"';
                    } else if ($this.val() !== "") {
                        tag += ' ' + $this.attr('name') + '="' + $this.val() + '"';
                    }
            }
        });

        tag += ']';
        return tag;

    };

    var insertTag = function() {

        var tag = buildTag() || "";
        var win = window.parent || window;
        if (typeof win.tinyMCE !== 'undefined' && (win.ed = win.tinyMCE.activeEditor) && !win.ed.isHidden()) {
            win.ed.focus();
            if (win.tinymce.isIE)
                win.ed.selection.moveToBookmark(win.tinymce.EditorManager.activeEditor.windowManager.bookmark);
            win.ed.execCommand('mceInsertContent', false, tag);
        } else {
            win.edInsertContent(win.edCanvas, tag);
        }
        // Close Lightbox
        win.tb_remove();
    };

    return {
        initialize: function() {
            if (typeof jQuery === 'undefined') {
                return;
            }
            jQuery("#generate").click(function(e) {
                e.preventDefault();
                if (jQuery('#mapid').val() === "") {
                    jQuery('#mapid').css('border', '1px solid red');
                    return "";
                }
                insertTag();
            });
        }
    };

}());