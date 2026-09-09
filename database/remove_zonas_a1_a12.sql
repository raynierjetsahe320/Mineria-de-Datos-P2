-- Delete zonas named A-1 .. A-12
DELETE FROM zonas WHERE nombre IN ('A-1','A-2','A-3','A-4','A-5','A-6','A-7','A-8','A-9','A-10','A-11','A-12');

-- If salones or incidencias reference these zonas, you may want to set zona_id to NULL first:
-- UPDATE incidencias SET zona_id = NULL WHERE zona_id IN (SELECT id FROM (SELECT id FROM zonas WHERE nombre LIKE 'A-%') x);
-- DELETE FROM zonas WHERE nombre LIKE 'A-%';
