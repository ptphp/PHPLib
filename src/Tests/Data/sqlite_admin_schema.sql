CREATE TABLE [pt_schema] (
	[id] integer NOT NULL PRIMARY KEY AUTOINCREMENT, 
	[tblname] text NOT NULL, 
	[name] text NOT NULL, 
	[title] text NOT NULL, 
	[maxl] integer DEFAULT 0, 
	[minl] integer DEFAULT 0, 
	[des] text, 
	[type] text, 
	[pk] bool DEFAULT 0, 
	[ac] bool DEFAULT 0, 
	[uq] bool DEFAULT 0, 
	[defaults] text, 
	[list] bool DEFAULT 0, 
	[listedit] bool DEFAULT 0, 
	[width] integer DEFAULT 0, 
	[height] integer DEFAULT 0, 
	[element] text, 
	[sets] text, 
	[enums] text, 
	[dec_m] integer DEFAULT 0, 
	[dec_d] integer DEFAULT 0, 
	[ord] integer DEFAULT 0, 
	[regx] text, 
	[yz] text, 
	[options] text
);

CREATE TABLE [pt_tables] (
	[id] integer NOT NULL PRIMARY KEY AUTOINCREMENT, 
	[name] text NOT NULL, 
	[title] text, 
	[charset] text, 
	[engine] text, 
	[auto_increment] integer, 
	[ord] integer DEFAULT 0
)