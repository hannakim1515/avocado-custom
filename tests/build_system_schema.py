"""Build the reviewed v1 contract from repository DDL, never execute source PHP/SQL.

Run after reviewing source changes; commit the resulting immutable contract together
with a module version change. Runtime only reads system_schema_v1.json.
"""
import copy
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def read(path):
    return (ROOT / path).read_text(encoding='utf-8-sig')


def split_sql(body):
    parts, start, depth, quote = [], 0, 0, None
    for i, c in enumerate(body):
        if quote:
            if c == quote and (not i or body[i-1] != '\\'):
                quote = None
        elif c in "'\"`":
            quote = c
        elif c == '(':
            depth += 1
        elif c == ')':
            depth -= 1
        elif c == ',' and depth == 0:
            parts.append(body[start:i].strip())
            start = i + 1
    return parts + [body[start:].strip()]


# Mapping only; no credentials are read from the site's dbconfig.
mapping = {}
for path in list((ROOT / 'extend').glob('*.php')) + [ROOT / 'k_battle/extend/default.php', ROOT / 'install/install_db.php']:
    text = path.read_text(encoding='utf-8-sig')
    for key, suffix in re.findall(r"\$g5\['(\w+)'\]\s*=\s*G5_TABLE_PREFIX\s*\.\s*'([^']+)'", text):
        mapping[key] = suffix
mapping.update(config_table='config', member_table='member', character_table='character',
               status_config_table='status', status_table='status_character', item_table='item',
               inventory_table='inventory', level_table='level_setting', shop_table='shop',
               k_realtime_table='k_battle_realtime')
tables = {}


def resolve(token):
    token = token.strip('`')
    m = re.fullmatch(r"\{\$g5\['(\w+)'\]\}", token)
    if m:
        return mapping[m[1]]
    if token.startswith('avo_'):
        return token[4:]
    raise ValueError('Unresolved table ' + token)


def table(module, name, source):
    return tables.setdefault(module, {}).setdefault(name, dict(columns={}, indexes={}, source=source, create=False))


def field(spec, definition):
    definition = re.sub(r'\s+', ' ', definition.strip().rstrip(';'))
    definition = re.sub(r'\s+(AFTER\s+`?\w+`?|FIRST)\s*$', '', definition, flags=re.I)
    idx = re.match(r'(PRIMARY KEY|UNIQUE (?:KEY|INDEX)|KEY|INDEX)\s*(?:`?(\w+)`?\s*)?\((.+)\)(?: USING \w+)?$', definition, re.I)
    if idx:
        kind, name, columns = idx.groups()
        cols = [v.strip().strip('`') for v in split_sql(columns)]
        name = 'PRIMARY' if kind.upper() == 'PRIMARY KEY' else name or cols[0]
        spec['indexes'][name] = dict(columns=cols, unique=kind.upper().startswith(('PRIMARY', 'UNIQUE')))
        return
    col = re.match(r'`?(\w+)`?\s+(.+)$', definition)
    if not col:
        raise ValueError(definition)
    name, ddl = col.groups()
    if re.search(r'\bPRIMARY KEY\b', ddl, re.I):
        spec['indexes']['PRIMARY'] = dict(columns=[name], unique=True)
        ddl = re.sub(r'\s+PRIMARY KEY', '', ddl, flags=re.I)
    spec['columns'][name] = ddl


def extract(module, path, only=None, alters=True):
    text = re.sub(r'/\*.*?\*/', '', read(path), flags=re.S)
    # Definitions end at a statement/string boundary, not an inner type parenthesis.
    pattern = r'CREATE TABLE (?:IF NOT EXISTS )?(`?\{\$g5\[\'\w+\'\]\}`?|`?avo_\w+`?)\s*\((.*?)\)\s*((?:ENGINE\s*=\s*\w+(?:\s+DEFAULT)?\s+CHARSET\s*=\s*\w+)?)\s*(?=[";])'
    for m in re.finditer(pattern, text, re.I | re.S):
        name = resolve(m[1])
        if only and name not in only:
            continue
        spec = table(module, name, path)
        spec['create'] = True
        engine = re.search(r'ENGINE\s*=\s*(\w+)', m[3], re.I)
        charset = re.search(r'CHARSET\s*=\s*(\w+)', m[3], re.I)
        spec['engine'] = engine[1] if engine else 'MyISAM'
        spec['charset'] = charset[1] if charset else 'utf8'
        # Only transactional subsystems require an engine. Legacy MyISAM may
        # already have been safely migrated to InnoDB by an operator.
        spec['require_engine'] = spec['engine'].lower() == 'innodb'
        for part in split_sql(m[2]):
            field(spec, part)
    if alters:
        for m in re.finditer(r'ALTER TABLE\s+(`?\{\$g5\[\'\w+\'\]\}`?|`?avo_\w+`?)\s+ADD\s+(.*?)(?=[";])', text, re.I | re.S):
            name = resolve(m[1])
            if only and name not in only:
                continue
            for part in split_sql(m[2]):
                field(table(module, name, path), re.sub(r'^ADD\s+', '', part, flags=re.I))


core = ['character', 'character_class', 'character_side', 'status', 'status_character',
        'item', 'inventory', 'level_setting', 'character_title', 'has_title', 'exp']
extract('base', 'install/gnuboard5.sql', only=core, alters=False)
for path in ['extend/status_extra.lib.php', 'extend/status_battle.lib.php', 'extend/skill.lib.php', 'adm/character_list.php']:
    extract('base', path)
field(table('base', 'member', 'install/gnuboard5.sql'), "ch_id int(11) NOT NULL DEFAULT '0'")
extract('map', 'extend/map.lib.php')
extract('npc', 'extend/npc.lib.php')
extract('dungeon', 'extend/dungeon.lib.php')
extract('k_battle', 'k_battle/extend/install/database_plugin.sql')
extract('unified', 'extend/unified_stat.lib.php')
for suffix, col in [('k_battle_skill', 'unified_a_sk_id'), ('k_battle_skill_ch', 'unified_a_sh_id')]:
    spec = table('unified', suffix, 'extend/unified_skill.lib.php')
    field(spec, col + " int(11) NOT NULL DEFAULT '0'")
    field(spec, 'KEY idx_' + col + ' (' + col + ')')
extract('realtime', 'k_battle/extend/install/database_realtime.sql')
spec = table('realtime', 'k_battle_plugin_config', 'adm/980_k_data_install.php; adm/982_k_realtime_config.php')
field(spec, "ver_realtime varchar(20) NOT NULL DEFAULT '1.0.0'")
field(spec, "realtime int(11) NOT NULL DEFAULT '1'")
text = read('extend/unified_skill.lib.php').split('$columns = array(', 1)[1].split(');', 1)[0]
for name, ddl in re.findall(r"'(\w+)'\s*=>\s*\"([^\"]+)\"", text):
    field(table('realtime', 'k_battle_realtime_skill', 'extend/unified_skill.lib.php'), name + ' ' + ddl)
extract('field', 'extend/field.lib.php')
extract('room', 'extend/room.config.php')
extract('quest', 'adm/993_quest_config.php')
spec = copy.deepcopy(tables['base']['inventory'])
spec['source'] = 'adm/993_quest_config.php (CREATE LIKE inventory; current custom columns checked at runtime)'
field(spec, "qu_id int(11) NOT NULL DEFAULT '0'")
tables['quest']['k_quest_inven'] = spec
extract('inventory_boundary', 'install/dungeon/002_inventory_boundary.manual.sql')
extract('labyrinth', 'install/dungeon/003_labyrinth.manual.sql')
spec = table('inventory_index', 'inventory', 'install/dungeon/004_inventory_index.manual.sql')
field(spec, 'KEY maze_owner_rows (ch_id,in_id)')
# Runtime accepts any full ch_id-leading index, as does manual migration 004.
spec['indexes']['maze_owner_rows']['prefix_ok'] = True
spec['indexes']['maze_owner_rows']['columns'] = ['ch_id']
spec['indexes']['maze_owner_rows']['add_columns'] = ['ch_id', 'in_id']

# Published K plugin SQL is the final union of 2.0.0 and admin bootstrap fields.
# Wider legacy VARCHAR and differing defaults remain compatible; no MODIFY.
for name, cols in {
    'k_battle_monster': ["raid_type varchar(50) NOT NULL DEFAULT 'realtime'", "mo_pattern int NOT NULL DEFAULT '1'", "mo_default_act int NOT NULL DEFAULT '1'", 'KEY idx_raid_type (raid_type)'],
    'k_battle_skill': ["unit_type varchar(10) NOT NULL DEFAULT ''", "raid_type varchar(50) NOT NULL DEFAULT 'realtime'", 'KEY idx_raid_type (raid_type)'],
}.items():
    for col in cols:
        field(tables['k_battle'][name], col)

out = ROOT / 'install/system_schema_v1.json'
out.write_text(json.dumps(tables, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
print('Contract:', len(tables), 'modules,', sum(len(v) for v in tables.values()), 'table contracts')
