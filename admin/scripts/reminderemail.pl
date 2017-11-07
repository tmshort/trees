#!/usr/bin/perl -w

use DBI;
use POSIX;
use Time::ParseDate;
use Mail::Sendmail;
use WWW::Wunderground::API;
use File::Basename;
use Cwd 'realpath';

my $ADMINUSER = "";
my $ADMINPASS = "";
my $MYSQL_HOST = "";
my $MYSQL_USER = "";
my $MYSQL_PASS = "";
my $DEFNAME = "";
my $DEFEMAIL = "";

my $config_file = realpath(dirname($0) . "/../../config.inc");

eval `cat $config_file`;

print "ADMINUSER = $ADMINUSER\n";
print "ADMINPASS = $ADMINPASS\n";
print "MYSQL_HOST = $MYSQL_HOST\n";
print "MYSQL_USER = $MYSQL_USER\n";
print "MYSQL_PASS = $MYSQL_PASS\n";
print "DEFNAME = $DEFNAME\n";
print "DEFEMAIL = $DEFEMAIL\n";

my $db="trees";

my $debug = 0;

my $dbh = DBI->connect("DBI:mysql:$db:$MYSQL_HOST", $MYSQL_USER, $MYSQL_PASS);

my @tomorrow = localtime(time() + 24 * 60 * 60);
my $start = strftime("%F 00:00:01", @tomorrow);
my $end = strftime("%F 23:59:59", @tomorrow);

if ($debug == 1) {
    $start = "2016-11-20 00:00:01";
    $end = "2016-11-30 23:59:59";
}

print "START: $start to $end\n";

my %shifts = ();
my %start = ();
my %end = ();

# Get list of shifts
my $query="SELECT id, start, end, description FROM shifts WHERE (shifts.start > '$start' AND shifts.end < '$end')";
my $sqlQuery = $dbh->prepare($query) or die "Can't prepare $query: $dbh->errstr\n";
my $rv = $sqlQuery->execute or die "Can't execute the query: $sqlQuery->errstr\n";
while (my @row = $sqlQuery->fetchrow_array()) {
    $start{$row[0]} = $row[1];
    $end{$row[0]} = $row[2];
    $shifts{$row[0]} = $row[3];
}
my $rc = $sqlQuery->finish;

my %allemails = ();
my $output = "";

foreach my $key (sort { $a <=> $b } keys %shifts) {

    my @names = ();
    my %emails = ();

##
## Inner join for the parent shifts
## 
    $query = "SELECT p.pname, p.email FROM parents p INNER JOIN parent_shifts ps ON ps.parentid = p.id WHERE ps.shiftid = '$key'";
    $sqlQuery = $dbh->prepare($query) or die "Can't prepare $query: $dbh->errstr\n";
    $rv = $sqlQuery->execute or die "Can't execute the query: $sqlQuery->errstr\n";
    while (my @row = $sqlQuery->fetchrow_array()) {
	push @names,$row[0] . " (or other parent)";
	$emails{$row[1]} = 1;
	$allemails{$row[1]} = 1;
    }
    $rc = $sqlQuery->finish;

##
## Inner join for the scout shifts
## 
    $query = "SELECT s.sname, s.email FROM scouts s INNER JOIN scout_shifts ss ON ss.scoutid = s.id WHERE ss.shiftid = '$key'";
    $sqlQuery = $dbh->prepare($query) or die "Can't prepare $query: $dbh->errstr\n";
    $rv = $sqlQuery->execute or die "Can't execute the query: $sqlQuery->errstr\n";
    while (my @row = $sqlQuery->fetchrow_array()) {
	push @names,$row[0];
	$emails{$row[1]} = 1;
	$allemails{$row[1]} = 1;
    }
    $rc = $sqlQuery->finish;

    if (@names) {
	$starttime = strftime("%A, %B %e, %l:%M %p", localtime(parsedate($start{$key})));
	$endtime = strftime("%l:%M %p", localtime(parsedate($end{$key})));
	$output .= "Shift #$key $shifts{$key}: $starttime to $endtime\n";
	$output =~ s/  / /g;
	foreach my $name (@names) {
	    $output .= "* $name\n";
	}
	$output .= "\n";
    }
}

my $wun = new WWW::Wunderground::API(location => "Sudbury, MA",
				     api_key => "7127a178c5563872",
				     auto_api => 1);
my $forecast1 = $wun->forecast10day->txt_forecast->forecastday->[2]{title};
my $forecast2 = $wun->forecast10day->txt_forecast->forecastday->[2]{fcttext};
my $forecast3 = $wun->forecast10day->txt_forecast->forecastday->[3]{title};
my $forecast4 = $wun->forecast10day->txt_forecast->forecastday->[3]{fcttext};

my @pemail = keys %allemails;
foreach my $m (@pemail) {
    $query = "SELECT email FROM emails WHERE pemail = '$m'";
    $sqlQuery = $dbh->prepare($query) or die "Can't prepare $query: $dbh->errstr\n";
    $rv = $sqlQuery->execute or die "Can't execute the query: $sqlQuery->errstr\n";
    while (my @row = $sqlQuery->fetchrow_array()) {
	$allemails{$row[0]} = 1;
    }
    $rc = $sqlQuery->finish;
}

my $to = join(",", keys %allemails);
my $subject = "Tree Sale Shift Reminder";
my $body = "";

$body .= "This is a reminder of your upcoming Tree Sale Shift. Please find your shift below.\n\n"; 
$body .= "Our tree sale is in the parking lot adjacent to Sullivan Tire on Boston Post Road (Rte. 20), Sudbury.\n\n";
$body .= "We ask the following:\n\n";
$body .= "1. Please remember to park in the back of the lot. Do not park in spaces reserved for our customers, Sullivan Tire's customers, nor Interstate Oil.\n";
$body .= "2. Please be on time.\n";
$body .= "3. Please dress appropriately; Scout shirts if possible, but dress for the weather!\n";
if (defined $forecast1 && $forecast1 ne "") {
    $body .= "* $forecast1: $forecast2\n* $forecast3: $forecast4\n";
} else {
    $body .= "* Sorry, unable to get forecast!\n";
}
$body .= "4. Please visit https://www.treesale.christmas/instructions.php for information on your shift.\n";
$body .= "\n";
$body .= $output;
$body .= "Thank you for supporting Troop 60!\n\n-$DEFNAME\n-Troop 60 Scheduler\n";

my $from = "$DEFNAME <$DEFEMAIL>";
my %message = (
    To      => $to,
    From    => $from,
    Subject => $subject,
    Message => $body,
    );

if ($debug == 1) {
    print "To: $to\n";
    print "From: $from\n";
    print "Subject: $subject\n";
    print "Message: $body\n";
} else {
    print "STEP 8\n";
    sendmail(%message) or die $Mail::Sendmail::error;
    print "STEP 9\n";
    print "\nLog: " . $Mail::Sendmail::log . "\n";
    print "To: $to\n";
    print "Body:\n$body\n";
}
