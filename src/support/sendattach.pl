#!/usr/bin/perl

# $Id: sendattach.pl,v 1.1.1.1 2007/11/29 15:29:45 ingoth Exp $
use Mail::Sender;

$to = $ARGV[0];
$file = $ARGV[1];
$sender = new Mail::Sender;
(ref ($sender->MailFile(
  {from => 'NBI Support Team<support@nbi.com.ua>',
	 to =>$to, 
	 smtp => 'localhost',
	 subject => 'Detailed report on Your request',
   msg => "Dear customer.\nYou can find requested report attached.",
   file => $file
  })) 
	
	) or die "$Mail::Sender::Error\n";

