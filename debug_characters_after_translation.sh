#!/bin/bash

# Debug script pour vérifier si les character_id sont préservés après traduction

PROJECT_ID=${1:-21}  # Projet par défaut: 21

echo "🔍 Vérification des character_id pour le projet ${PROJECT_ID}"
echo "================================================================"
echo ""

cd /Users/martinp/Documents/GitHub/agfaRythmo/agfa-rythmo-backend

echo "📊 Distribution des character_id:"
sqlite3 database/database.sqlite << SQL
SELECT 
    character_id,
    COUNT(*) as count,
    GROUP_CONCAT(id, ', ') as timecode_ids
FROM timecodes 
WHERE project_id = ${PROJECT_ID}
GROUP BY character_id
ORDER BY character_id;
SQL

echo ""
echo "👥 Personnages du projet:"
sqlite3 database/database.sqlite << SQL
SELECT 
    id,
    name,
    color
FROM characters
WHERE project_id = ${PROJECT_ID}
ORDER BY id;
SQL

echo ""
echo "📝 Premiers timecodes avec leur personnage:"
sqlite3 database/database.sqlite << SQL
.mode column
.headers on
SELECT 
    t.id,
    t.character_id,
    c.name as character_name,
    SUBSTR(t.text, 1, 40) as text
FROM timecodes t
LEFT JOIN characters c ON t.character_id = c.id
WHERE t.project_id = ${PROJECT_ID}
ORDER BY t.id
LIMIT 15;
SQL

echo ""
echo "✅ Terminé"
