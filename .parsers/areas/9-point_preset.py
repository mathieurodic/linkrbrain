from db import connection, cursor, pointsCache
from math import sqrt
from time import time


print '\nDeleting all the points-presets scores...'
cursor.execute(
'''     DELETE FROM
                point_preset
        WHERE
                preset_type = "area"
''')


print '\nGetting all the presets...'
cursor.execute(
'''     SELECT
                type,
                id,
                rscore
        FROM
                preset
        WHERE
                type = "area"
''')
rows = cursor.fetchall()


print '\nUpdating...'
t0 = time()
for preset_type, preset_id, rscore in rows:
        t1 = time()

        cursor.execute(
        '''     INSERT INTO
                        point_preset
                SELECT
                        p_p.point2_id AS point_id,
                        %s AS preset_type,
                        %s AS preset_id,
                        SUM(p_p.score) AS score,
                        SUM(p_p.score) / %s AS nscore
                FROM
                        preset_point AS pr_p
                INNER JOIN
                        point_point AS p_p ON p_p.point1_id = pr_p.point_id
                WHERE
                        pr_p.preset_type = %s
                AND
                        pr_p.preset_id = %s
                GROUP BY
                        p_p.point2_id
        ''', (preset_type, preset_id, rscore, preset_type, preset_id, ))

        t = time()
        print '%s %-5d  [ %-4f | %4f ]' % (preset_type, preset_id, t-t0, t-t1, )