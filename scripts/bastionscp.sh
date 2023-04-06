if [ $# -ne 3 ]; then
  echo "wrong number of arguments"
  exit
fi

scp -o "ForwardAgent yes" -o "ProxyJump ec2-user@$1" -o 'User ec2-user' $2 $3