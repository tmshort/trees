#!/usr/bin/perl -w

use WWW::Wunderground::API;
use Data::Dumper;

my $wun = new WWW::Wunderground::API(location => "Sudbury, MA",
				     api_key => "7127a178c5563872",
				     auto_api => 1);

my $forecast1 = $wun->forecast10day->txt_forecast->forecastday->[2]{title};
my $forecast2 = $wun->forecast10day->txt_forecast->forecastday->[2]{fcttext};
my $forecast3 = $wun->forecast10day->txt_forecast->forecastday->[3]{title};
my $forecast4 = $wun->forecast10day->txt_forecast->forecastday->[3]{fcttext};


#print $wun;
#print Dumper($wun->forecast10day->txt_forecast->forecastday);
#my @days = @{$wun->forecast10day->txt_forecast->forecastday};
#print Dumper(@days);
#my %day = %{$days[2]};
#print Dumper(\%day);
#$forecast1 = $day{'title'};
#$forecast2 = $days[2]{fcttext};
#$forecast3 = $days[3]{title};
if (!defined $forecast1) {
    $forecast1 = "undefined";
}
if (!defined $forecast2) {
    $forecast2 = "undefined";
}
if (!defined $forecast3) {
    $forecast3 = "undefined";
}
if (!defined $forecast4) {
    $forecast4 = "undefined";
}
print "forecast1: $forecast1\n";
print "forecast2: $forecast2\n";
print "forecast3: $forecast3\n";
print "forecast4: $forecast4\n";

