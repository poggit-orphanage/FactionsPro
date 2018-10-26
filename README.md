# FactionsPro

A modified plugin, better than ever. Being updated frequently. Edited by the VMPE development team. We update this plugin most of the time, features, bug fixes, and more. If you have any issues, or suggestions on what we should fix / add to this plugin, please open a new issue. We will make sure to read them. Thank you.

## What is this branch?
This branch is called "4.0.0-API". This branch allows us to update the plugin to 4.0.0 before they release to the public. That way, if some people are using the development builds for Pocketmine 4.0.0 API, then this branch will be good for them. That way, they can now get the API 4.0.0. We're still looking into issues raised with 4.0.0 API so we can get them updated to the latest API ASAP. If you have any issues regarding this branch and you're having issues with the PMMP 4.0.0 API, then you can open an issue.

## Features
* All the features from the good 'ol' days are now back, and working with PMMP Latest APi's.

* /f map = Better than ever.

* Factions, more customised, and bigger than ever.

* Prefixes - You can configure the command prefixes in which you can use on every single command.

* F Values - You can use Faction money, balance, withdraw, donate, and more.

* /f say now works - It's better than ever, and /f say is finally working!

* Better Factions help page! Use /f help for the usage!

* /f claim now works as it should - We've fixed /f claim, and it's better than ever!

* /f overclaim now works as it should - In this version of FactionsPro, we've made it so you can Overclaim! This was also introduced in the Old Modded version of this plugin.

* Made configurations so much easier!

* You can now: Forcefully remove someone's power, and balance with a simple command.

* And still updating as we speak!


## Faction Commands

/f create <faction> - Create a faction.
  
/f delete <faction> - Delete a faction.
  
/f invite <name> - Invite a player to your faction.
  
/f accept - Accepts a leader's invitation to join your faction.

/f deny - Denies a leader's invitation to join your faction.

/f who - Shows info about your faction.

/f who <faction> - Shows other faction information.
  
/f leave - Quit's a faction.

/f kick <name> - Kicks a player from your faction.
  
/f balance - Shows your faction money.

/f top money - Shows the top 10 Richest factions.

/f top str - Shows the top 10 Best Factions.

/f top - Shows the /f top usage.

/f enemy <faction> - Enemies with a faction.
  
/f notenemy <faction> - Undeclare an faction enemy.
  
/f ally <faction> - Requests an alli with a faction.
  
/f allyok - Accepts an ally request.

/f allyno - Denies a ally request.

/f withdraw <amount> - Transfer money from your faction bank.
  
/f donate <amount> - Transfer money from your Economy Money.
  
/f war <faction>:tp - Requests a war with a faction.
  
/f say - Broadcast a message to your faction.

/f chat - Toggle on / off faction chat.

/f ac - Toggle on / off Ally chat.

/f promote <name> - Promote a player in your faction.
  
/f demote <name> - Demote a player in your faction.
  
/f listleader - Lists the leader in your faction.

/f listofficers - Lists the officers in your faction.



## Admin Commands

/f addstrto - Adds strength to a faction.

/f forcedelete - Force deletes a faction.

/f forceunclaim - Force unclaims a faction's plot.

/f rmbalto - Force removes a faction's money.

/f rmpower - Force removes a faction's STR / power.

## LATEST TO-DO LIST
- [X] Update to 4.0.0 API
- [X] Reformat plugin
- [X] Bump API version to 4.0.0
- [X] Bump Plugin version to 3.0.0-BETA
- [X] Implement Accept_time & deny_time configurations (Untested)
- [X] Fix onEnable and on Disable() function, and make them protected due to the latest API updates.
- [X] Add more return types, and fixes relating to the outdated code (Untested)
- [X] Remove useless imports that aren't needed.
- [X] No spoons allowed implementation
- [X] System bug fixes and much improvements.
- [X] Add the following checks for the onEnable() check: checkConfigurations(), registerEvents(), checkPlugins(), checkOriginal(), and checkSpoon()
- [X] Add onLoad() function and make that protected.
- [X] Add new folders: tasks, and listeners for FactionWar and FactionListener, instead of making it more confusing.
- [X] Rename FactionWar to FactionWarTask so people know it's a task rather than just a normal class file.
- [ ] Make this plugin stable. (There's still lots to do.)
- [ ] Release out of the BETA stages.
- [ ] Merge to beta branch (When complete and released.)

If you want anything else added, please open a issue. Thank you.
