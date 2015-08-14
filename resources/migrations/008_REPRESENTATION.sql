ALTER TABLE "organism_representation" ADD "url" TEXT NULL;
ALTER TABLE "organism_representation" ALTER "rights_holder" TYPE text, ALTER "rights_holder" DROP DEFAULT, ALTER "rights_holder" DROP NOT NULL;
ALTER TABLE "organism_representation" ALTER "source" TYPE text, ALTER "source" DROP DEFAULT, ALTER "source" DROP NOT NULL;