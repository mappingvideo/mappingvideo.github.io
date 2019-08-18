     <div id="rightcontent">
        {include file="errmsg.tpl"}
        <div id="right">
        <div align="center">
        <div id="simpleForm">
        <form name="embedVideo" method="POST" enctype="multipart/form-data" action="videos.php?m=membed">
        <fieldset>
        <legend>Mass Embed Video</legend>
			<label for="url">URL:</label>
			<input name="url" type="text" value="{$embed.url}" class="large" /><br />
            <label for="username">Username: </label>
            <input name="username" type="text" value="{$embed.username}" /><br>
            <label for="category">Category: </label>
            <select name="category">
			<option value="">Autodetect</option>
            {section name=i loop=$categories}
            <option value="{$categories[i].CHID}"{if $video.category == $categories[i].CHID} selected="selected"{/if}>{$categories[i].name|escape:'html'}</option>
            {/section}
            </select><br />
			<label for="status">Status: </label>
			<select name="status" id="status">
			<option value="1"{if $embed.status == '1'} selected="selected"{/if}>Active</option>
			<option value="0"{if $embed.status == '0'} selected="selected"{/if}>Suspended</option>
			</select>
        </fieldset>
        <div style="text-align: center;">
            <input name="membed_videos" type="submit" value="-- Embed Videos --" id="save_video_button" class="button" onClick="document.getElementById('save_video_button').value='-- Embedding... --'";>
        </div>
        </form>
        </div>
        </div>
        </div>
     </div>
