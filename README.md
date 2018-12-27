# MyBB-SteamActivation-CLWO-Staff_Assigner_Plugin
Plugin for clwo.eu that lets you call xmlhttp with certain values to set forum ranks.

This plugin needs the MyBB-SteamActivation plugin in order to work.
This plugin is designed specifically for the clwo.eu community.

Needs to be called as follows:
[website]/xmlhttp.php?staff_rank_change=true&code=[code]&steamid=[steamid64]&title=[new forum usertitle (text)]&[rank1name]=[true/false]&[rank2name]=[true/false]&[...]
More settings are explained in the MyBB plugin menu
The title field can be left out. In this case, the usertitle will be removed.
Usertitles are never affected if a user has the CustomUsertitle rank (specified in the settings)
