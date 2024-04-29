<?php
/**
 * Template to isplay schedule in horizontal format
 *
 * May be loaded along with grid schedule to be swapped via DOM request
 *
 * @link  http://mzoo.org
 * @since 2.4.7
 * @package MzMindbody
 *
 * @author Mike iLL/mZoo.org
 */

use MZoo\MzMindbody\Core;
use MZoo\MzMindbody\Libraries as Libraries;
use MZoo\MzMindbody as NS;

?>
<?php
if ( empty( $data->horizontal_schedule ) ) {
    echo sprintf(
        // translators: Give a start and end date range for displayed classes.
        __( 'No Classes To Display (%1$s - %2$s)', 'mz-mindbody-api' ),
        gmdate( $data->date_format, $data->start_date->getTimestamp() ),
        gmdate( $data->date_format, $data->end_date->getTimestamp() )
    );
}
?>
<h1>Registrants Listing</h1>
<div id="mz_horizontal_schedule" class="registrants-listing">
    <?php foreach ( $data->horizontal_schedule as $day => $classes ) : ?>
        <details>
            <summary>
                <?php echo gmdate( $data->date_format, strtotime( $day ) ); ?>
            </summary>

            <?php if ( ! empty( $classes ) ) : ?>
                <ul>
                <?php foreach ( $classes as $k => $class ) : ?>
                    <li>
                    <?php echo gmdate( $data->time_format, strtotime( $class->start_datetime ) ) . ' - ' . gmdate( $data->time_format, strtotime( $class->end_datetime ) ); ?>
                        <span class="mz_hidden mz_time_of_day"><?php echo $class->part_of_day; ?></span>

                    <?php
                    $class->class_name_link->output();
                    ?>
                    <?php echo $class->display_cancelled; ?>
                    with

                        <?php
                        $class->staff_name_link->output();
                        ?>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <?php esc_html_e( 'No classes to display.', 'mz-mindbody-api' ); ?>
            <?php endif; // if $classes or else block. ?>
        </details>

        </div>
    <?php endforeach; ?>
</div>
