### General
#### FactionsPro
[![Poggit Release](https://poggit.pmmp.io/shield.approved/FactionsPro)](https://poggit.pmmp.io/p/FactionsPro)


A fully featured Factions plugin for PMMP PocketMine-MP

### Features

Easily create, delete, and manage factions

Players in the same faction don't inflict PVP damage on each other

Kick annoying players

Invite anyone you want (they can accept or deny the invite)

Claim plots and create a dominating base

Three ranks: Member, Officer and Leader

### Commands

```
/f about
/f allychat
/f accept
/f overclaim [Takeover the plot of the requested faction]
/f chat
/f claim
/f create <name>
/f del
/f demote <player>
/f deny

/f home
/f help <page>
/f info
/f info <faction>
/f invite <player>
/f kick <player>
/f leader <player>
/f leave

/f sethome
/f unclaim
/f unsethome
/f ourmembers - {Members + Statuses}
/f ourofficers - {Officers + Statuses}
/f ourleader - {Leader + Status}
/f allies - {The allies of your faction

/f desc
/f promote <player>
/f allywith <faction>
/f breakalliancewith <faction>

/f allyok [Accept a request for alliance]
/f allyno [Deny a request for alliance]
/f allies <faction> - {The allies of your chosen faction}

/f membersof <faction>
/f officersof <faction>
/f leaderof <faction>
/f say <send message to everyone in your faction>
/f pf <player>
/f topfactions

/f forceunclaim <faction> [Unclaim a faction plot by force - OP]

/f forcedelete <faction> [Delete a faction by force - OP]
```
### Permissions

There is only one permission for all the faction commands. Some commands are OP only.
 
`f.command` - access to FactionsPro user commands.

### Extras

Add PureChat to display factions in chat, and AntiSpamPro to prevent inappropriate fac names

### Config

Configure FactionsPro with Prefs.yml file in the FactionsPro plugin folder. Example Config:

```
MaxFactionNameLength: 15
MaxPlayersPerFaction: 30
OnlyLeadersAndOfficersCanInvite: true
OfficersCanClaim: false
PlotSize: 25
PlayersNeededInFactionToClaimAPlot: 1
PowerNeededToClaimAPlot: 0
PowerNeededToSetOrUpdateAHome: 250
PowerGainedPerPlayerInFaction: 50
PowerGainedPerKillingAnEnemy: 10
PowerGainedPerAlly: 100
TheDefaultPowerEveryFactionStartsWith: 0
EnableOverClaim: true
AllyLimitPerFaction: 5
ClaimWorlds:
- world
AllowChat: true
ClaimingEnabled: true
Member:
  claim: false
  demote: false
  home: true
  invite: false
  kick: false
  motd: false
  promote: false
  sethome: false
  unclaim: false
  unsethome: false
Officer:
  claim: true
  demote: false
  home: true
  invite: true
  kick: true
  motd: true
  promote: false
  sethome: true
  unclaim: true
  unsethome: true
AllowFactionPvp: false
AllowAlliedPvp: false
```

### Credits

Credit and thanks to TETHERED for writing this plugin, and various other teams who contributed other parts of the code. Let us know if you'd like credit, we don't know who you all are.


