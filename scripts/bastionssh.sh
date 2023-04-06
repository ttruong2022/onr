if [ $# -ne 2 ]; then
  echo "wrong number of arguments"
  exit
fi

ssh -J ec2-user@$1 ec2-user@$2