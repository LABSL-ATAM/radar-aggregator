#!/usr/bin/perl
######################################################################
# Radar coso 
######################################################################
use strict;
#use warnings;
use autodie;
use feature         "say";
use POSIX           "strftime";
use Getopt::Std;
use Pod::Usage;
use Data::Dumper;
use File::Slurp     qw(read_file write_file);

#pal rss
use XML::FeedPP;
use DateTime;
use DateTime::Format::W3CDTF; 
use XML::Entities;
use HTML::Entities  "decode_entities";
use JSON            "to_json";
use utf8;

# O P T s
my %opts = ();
getopts('dhf:',\%opts);

=pod

=head1 SYNOPSIS

Radar: Bajador de feeds rss y atom..

=head2 Forma de uso:

=over

=item -f [Archivo csv con feeds a parsear/bajar]

=item -d debug 

=back

=cut

######################################################################
# VARIABLES GLOBALES
######################################################################
my $debug = $opts{d};
my $t_banana = strftime ("%d_%B_%Y",localtime(time()));
my $archivo_urls_categorias_csv = 'feeds.csv';
my @uri_rss_all     = ();
my %RSS             = ();
my @HOY             = ();
my $hoy             = DateTime->now(@_)->truncate( to => 'minute' );
my $un_dia          = 60 * 60 * 24 + 10;


######################################################################
# M A I N
######################################################################
if ($opts{h}){
	ayudas();
	exit 0;
}

if ($opts{f}){
	die unless -e $opts{f};
	$archivo_urls_categorias_csv = $opts{f};
}

# Recontra-Main   
feeds_list($archivo_urls_categorias_csv);
print Dumper(%RSS) if $debug;
url_getter();

=pod

=head2

Al final se pasa el hasref a un json!

=cut

my %fs = ( feeds => \@HOY );
my $C = \%fs;
my $AA = to_json( $C , { 
    #utf8 => 1, 
    pretty => 1 } 
    );
my $archivo_salida_hoy = $t_banana . '.json';
write_file($archivo_salida_hoy, { binmode => ':utf8' }, $AA);
#write_file($archivo_salida_hoy, $AA);

#fin
exit 0;

######################################################################
# S U B S
######################################################################
sub feeds_list {
	my $file_name = shift;
	my $n = 0;
	my @datas = read_file($file_name);
	foreach my $ln (@datas){
		chomp($ln);
		my @r = split(/,/,$ln);
		print Dumper(@r) if $debug;
        my $pri = shift @r;
		$RSS{$pri} = \@r;
		push (@uri_rss_all,$pri);
		$n++;
	}
}

=pod

=head2 FeedPP

La funcion url_getter hace todo el trabajo con los Rss.

Utiliza XML::FeedPP.

=cut

sub url_getter {
	foreach my $uri_rss (@uri_rss_all){
		my $feed = XML::FeedPP->new($uri_rss);
		my $nro = 0;
        my @entries = ();
		for my $entry ($feed->get_item()){
			print Dumper($entry) if $debug;
		    my %entries_hoy = ();
            my $es_de_hoy = 0;
			
			# FIJARSE SI ES NUEVO (HASTA HACE UN DIA ATRAS)
			my $chiotto = DateTime::Format::W3CDTF->new;
			my $tiempo_desde_creacion_del_entry_pre = $chiotto->parse_datetime($entry->pubDate);
			my $tt_entry = DateTime->from_object( object => $tiempo_desde_creacion_del_entry_pre);
			my $tiempo_desde_creacion_del_entry = $hoy->epoch() - $tiempo_desde_creacion_del_entry_pre->epoch();
			say $tiempo_desde_creacion_del_entry if $debug;
			
			if ($tiempo_desde_creacion_del_entry <= $un_dia + 20){
				say "es de hoy" if $debug;
				$entries_hoy{'title'}   = decode_shits($entry->title);
				$entries_hoy{'author'}  = decode_shits($entry->author);
				$entries_hoy{'link'}    = decode_shits($entry->link);
				$entries_hoy{'content'} = decode_shits($entry->description);
				$entries_hoy{'time'}    = tiempo_lindo($entry->pubDate);
                $entries_hoy{'feed_categories'} = $RSS{$uri_rss}; # ArrayRef !!
                $es_de_hoy++;
			}
            if ($es_de_hoy == 1){
                my $HE = \%entries_hoy;
                push(@entries,$HE);
                $nro++;
            }
		}
        if ($nro >= 1){
            my %hash_pal_key = (    name =>$feed->title, url =>$feed->link  );
            $hash_pal_key{entries} = \@entries;
            my $HK = \%hash_pal_key;
            push (@HOY,$HK);
        }
	}
}

sub decode_shits {
	my $shit = shift;
	#my $coso_sin_codificar = XML::Entities::decode('all',XML::Entities::numify('all',decode_entities($shit)));
	my $coso_sin_codificar = XML::Entities::decode('all', decode_entities($shit));
    my $puto = 'Â';
    $coso_sin_codificar =~ s/\Q$puto\E//g;
	return $coso_sin_codificar;
}

sub tiempo_lindo {
	my $st = shift;
	my $fecha_bien_laputaquetepario = $st;
	$fecha_bien_laputaquetepario    =~ s/([^T]+)T([^T]+)/\2 \1/g;
	return $fecha_bien_laputaquetepario;    
}

sub ayudas {
	pod2usage(-verbose=>3);
}
__DATA__
